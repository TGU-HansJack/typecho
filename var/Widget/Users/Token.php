<?php

namespace Widget\Users;

use Typecho\Common;
use Typecho\Widget\Helper\Form;
use Widget\Base\Users;
use Widget\Notice;
use Typecho\Db;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 用户令牌管理组件
 */
class Token extends Users
{
    /**
     * 生成用户专用JWT令牌
     * 
     * @param string $purpose 令牌用途
     * @return array 令牌信息数组
     */
    public function generateUserToken(string $purpose = 'API Access'): array
    {
        // 获取API配置
        $apiConfig = require __DIR__ . '/../../../api/config.php';
        $lifetime = $apiConfig['JWT']['lifetime'] ?? 86400; // 默认24小时
        
        // 使用用户特定的密钥而不是全局API_KEY
        $secretKey = $this->user->authCode ?? md5($this->user->uid . $this->user->name);
        
        $header = rtrim(strtr(base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode([
            'uid' => $this->user->uid,
            'name' => $this->user->name,
            'screenName' => $this->user->screenName,
            'exp' => time() + $lifetime,
            'iat' => time(),
            'iss' => $apiConfig['JWT']['issuer'] ?? 'Typecho API',
            'aud' => $apiConfig['JWT']['audience'] ?? 'Typecho Client'
        ])), '+/', '-_'), '=');
        
        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $header . '.' . $payload, $secretKey, true)), '+/', '-_'), '=');
        
        $token = $header . '.' . $payload . '.' . $signature;
        
        // 保存令牌到数据库
        $db = Db::get();
        $tokenId = $db->query($db->insert('table.user_tokens')->rows([
            'userId' => $this->user->uid,
            'token' => $token,
            'purpose' => $purpose,
            'created' => time(),
            'expired' => time() + $lifetime
        ]));
        
        return [
            'id' => $tokenId,
            'token' => $token,
            'purpose' => $purpose,
            'created' => time(),
            'expired' => time() + $lifetime
        ];
    }
    
    /**
     * 获取用户的所有令牌
     * 
     * @return array 令牌列表
     */
    public function getUserTokens(): array
    {
        $db = Db::get();
        return $db->fetchAll($db->select()->from('table.user_tokens')
            ->where('userId = ?', $this->user->uid)
            ->order('created', Db::SORT_DESC));
    }
    
    /**
     * 获取特定令牌
     * 
     * @param int $id 令牌ID
     * @return array|null 令牌信息
     */
    public function getUserToken(int $id): ?array
    {
        $db = Db::get();
        return $db->fetchRow($db->select()->from('table.user_tokens')
            ->where('userId = ?', $this->user->uid)
            ->where('id = ?', $id)
            ->limit(1));
    }
    
    /**
     * 删除用户令牌
     * 
     * @param int $id 令牌ID
     * @return int 删除的记录数
     */
    public function deleteUserToken(int $id): int
    {
        $db = Db::get();
        return $db->query($db->delete('table.user_tokens')
            ->where('userId = ?', $this->user->uid)
            ->where('id = ?', $id));
    }
    
    /**
     * 生成新令牌表单
     * 
     * @return Form
     */
    public function tokenForm(): Form
    {
        /** 构建表格 */
        $form = new Form($this->security->getIndex('/action/users-token'), Form::POST_METHOD);
        
        // 令牌用途
        $purpose = new Form\Element\Text('purpose', null, 'API Access', _t('令牌用途'), _t('请输入此令牌的用途，例如：移动应用、第三方服务等'));
        $purpose->input->setAttribute('class', 'w-100');
        $form->addInput($purpose->addRule('required', _t('必须填写令牌用途')));
        
        // 生成新令牌按钮
        $generateToken = new Form\Element\Submit('generate_token', null, _t('生成新的JWT令牌'));
        $generateToken->input->setAttribute('class', 'btn primary');
        $form->addItem($generateToken);
        
        // 操作类型
        $do = new Form\Element\Hidden('do', null, 'generate');
        $form->addInput($do);
        
        return $form;
    }
    
    /**
     * 令牌列表表格
     * 
     * @return void
     */
    public function tokenTable()
    {
        $tokens = $this->getUserTokens();
        ?>
        <div class="typecho-table-wrap">
            <table class="typecho-list-table">
                <colgroup>
                    <col width="50%"/>
                    <col width="30%"/>
                    <col width="20%"/>
                </colgroup>
                <thead>
                    <tr>
                        <th><?php _e('令牌用途'); ?></th>
                        <th><?php _e('创建时间'); ?></th>
                        <th><?php _e('操作'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tokens)): ?>
                    <tr>
                        <td colspan="3"><h6 class="typecho-list-table-title"><?php _e('没有任何令牌'); ?></h6></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($tokens as $token): ?>
                        <tr id="token-<?php echo $token['id']; ?>">
                            <td><?php echo htmlspecialchars($token['purpose']); ?></td>
                            <td><?php echo date('Y-m-d H:i:s', $token['created']); ?></td>
                            <td>
                                <a href="<?php echo $this->security->getIndex('/action/users-token?do=view&id=' . $token['id']); ?>" class="view-token"><?php _e('查看'); ?></a>
                                | 
                                <a href="<?php echo $this->security->getIndex('/action/users-token?do=delete&id=' . $token['id']); ?>" class="delete-token"><?php _e('删除'); ?></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * 生成新令牌操作
     */
    public function generateToken()
    {
        // 生成新令牌
        $tokenInfo = $this->generateUserToken($_POST['purpose'] ?? 'API Access');
        
        // 将令牌保存在会话中，供用户复制
        \Typecho\Cookie::set('user_token', $tokenInfo['token'], time() + 600); // 10分钟有效
        
        // 设置提示信息
        Notice::alloc()->set(_t('新的JWT令牌已生成，请复制下方二维码中的信息'), 'success');
        $this->response->goBack();
    }
    
    /**
     * 获取用于二维码的令牌信息
     * 
     * @param int $id 令牌ID
     * @return array
     */
    public function getQrCodeInfo(int $id): array
    {
        $tokenInfo = $this->getUserToken($id);
        if (!$tokenInfo) {
            return [];
        }
        
        return [
            'uid' => $this->user->uid,
            'name' => $this->user->name,
            'screenName' => $this->user->screenName,
            'token' => $tokenInfo['token'],
            'purpose' => $tokenInfo['purpose']
        ];
    }
    
    /**
     * 查看令牌
     * 
     * @param int $id 令牌ID
     * @return void
     */
    public function viewToken(int $id)
    {
        $tokenInfo = $this->getUserToken($id);
        if (!$tokenInfo) {
            Notice::alloc()->set(_t('指定的令牌不存在'), 'error');
            $this->response->goBack();
            return;
        }
        
        // 设置提示信息
        Notice::alloc()->set(_t('以下是您的JWT令牌信息，请妥善保管'), 'success');
        
        // 构建包含令牌信息的页面
        echo '<div class="token-info">';
        echo '<h3>' . _t('令牌信息') . '</h3>';
        echo '<p><strong>' . _t('用途：') . '</strong>' . htmlspecialchars($tokenInfo['purpose']) . '</p>';
        echo '<p><strong>' . _t('令牌内容：') . '</strong></p>';
        echo '<textarea class="w-100" rows="5" readonly="readonly" style="background-color:#f8f8f8;">' . htmlspecialchars($tokenInfo['token']) . '</textarea>';
        echo '<p><small>' . _t('该令牌创建于：') . date('Y-m-d H:i:s', $tokenInfo['created']) . '</small></p>';
        echo '</div>';
    }
    
    /**
     * 删除令牌
     * 
     * @param int $id 令牌ID
     * @return void
     */
    public function deleteToken(int $id)
    {
        $count = $this->deleteUserToken($id);
        if ($count > 0) {
            Notice::alloc()->set(_t('令牌已成功删除'), 'success');
        } else {
            Notice::alloc()->set(_t('指定的令牌不存在或删除失败'), 'error');
        }
        $this->response->goBack();
    }
}