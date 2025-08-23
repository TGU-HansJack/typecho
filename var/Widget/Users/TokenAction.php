<?php

namespace Widget\Users;

use Typecho\Common;
use Typecho\Widget\Helper\Form;
use Widget\Base\Users;
use Widget\Notice;
use Typecho\Db;
use Typecho\Cookie;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 用户令牌操作组件
 */
class TokenAction extends Users
{
    /**
     * 执行操作
     * 
     * @access public
     * @return void
     */
    public function action()
    {
        // 检查用户是否登录
        $this->user->pass('subscriber');
        
        // 设置动作集合
        $this->on($this->request->is('do=generate'))->generateToken();
        $this->on($this->request->is('do=view'))->viewToken();
        $this->on($this->request->is('do=delete'))->deleteToken();
        
        $this->response->goBack();
    }
    
    /**
     * 生成新令牌操作
     */
    public function generateToken()
    {
        // 获取表单数据
        $purpose = $this->request->purpose ?? 'API Access';
        
        // 生成新令牌
        $tokenWidget = Token::alloc();
        $tokenInfo = $tokenWidget->generateUserToken($purpose);
        
        // 设置提示信息
        Notice::alloc()->set(_t('新的JWT令牌已生成'), 'success');
        $this->response->goBack();
    }
    
    /**
     * 查看令牌详情
     */
    public function viewToken()
    {
        $id = $this->request->id ?? 0;
        
        if (!$id) {
            $this->response->throwJson([
                'success' => false,
                'message' => '令牌ID不能为空'
            ]);
        }
        
        $tokenWidget = Token::alloc();
        $tokenInfo = $tokenWidget->getUserToken($id);
        
        if (!$tokenInfo) {
            $this->response->throwJson([
                'success' => false,
                'message' => '令牌不存在'
            ]);
        }
        
        // 返回令牌详情
        $this->response->throwJson([
            'success' => true,
            'id' => $tokenInfo['id'],
            'token' => $tokenInfo['token'],
            'purpose' => $tokenInfo['purpose'],
            'qrCodeInfo' => $tokenWidget->getQrCodeInfo($id)
        ]);
    }
    
    /**
     * 删除令牌操作
     */
    public function deleteToken()
    {
        $id = $this->request->id ?? 0;
        
        if (!$id) {
            Notice::alloc()->set(_t('令牌ID不能为空'), 'error');
            $this->response->goBack();
            return;
        }
        
        $tokenWidget = Token::alloc();
        $result = $tokenWidget->deleteUserToken($id);
        
        if ($result) {
            Notice::alloc()->set(_t('令牌已删除'), 'success');
        } else {
            Notice::alloc()->set(_t('删除令牌失败'), 'error');
        }
        
        $this->response->goBack();
    }
}