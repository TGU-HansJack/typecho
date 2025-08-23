<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
$tokenWidget = \Widget\Users\Token::alloc();
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main">
            <div class="col-mb-12 col-tb-3">
                <p><a href="https://gravatar.com/emails/"
                      title="<?php _e('在 Gravatar 上修改头像'); ?>"><?php echo '<img class="profile-avatar" src="' . \Typecho\Common::gravatarUrl($user->mail, 220, 'X', 'mm', $request->isSecure()) . '" alt="' . $user->screenName . '" />'; ?></a>
                </p>
                <h2><?php $user->screenName(); ?></h2>
                <p><?php $user->name(); ?></p>
                <p><?php _e('目前有 <em>%s</em> 篇日志, 并有 <em>%s</em> 条关于你的评论在 <em>%s</em> 个分类中.',
                        $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?></p>
                <p><?php
                    if ($user->logged > 0) {
                        $logged = new \Typecho\Date($user->logged);
                        _e('最后登录: %s', $logged->word());
                    }
                    ?></p>
            </div>

            <div class="col-mb-12 col-tb-6 col-tb-offset-1 typecho-content-panel" role="form">
                <section>
                    <h3><?php _e('个人资料'); ?></h3>
                    <?php \Widget\Users\Profile::alloc()->profileForm()->render(); ?>
                </section>

                <?php if ($user->pass('contributor', true)): ?>
                    <br>
                    <section id="writing-option">
                        <h3><?php _e('撰写设置'); ?></h3>
                        <?php \Widget\Users\Profile::alloc()->optionsForm()->render(); ?>
                    </section>
                <?php endif; ?>

                <br>

                <section id="change-password">
                    <h3><?php _e('密码修改'); ?></h3>
                    <?php \Widget\Users\Profile::alloc()->passwordForm()->render(); ?>
                </section>

                <br>

                <section id="api-token">
                    <h3><?php _e('API访问令牌'); ?></h3>
                    <p><?php _e('您可以创建多个JWT令牌用于访问Typecho RESTful API，而无需使用全局API密钥。'); ?></p>
                    
                    <?php $tokenWidget->tokenTable(); ?>
                    
                    <br>
                    
                    <h4><?php _e('创建新的API令牌'); ?></h4>
                    <?php $tokenWidget->tokenForm()->render(); ?>
                </section>

                <?php \Widget\Users\Profile::alloc()->personalFormList(); ?>
            </div>
        </div>
    </div>
</div>

<!-- 令牌查看模态框 -->
<div id="token-modal" class="modal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php _e('令牌详情'); ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><?php _e('用途'); ?></label>
                    <p id="token-purpose" class="form-control-static"></p>
                </div>
                <div class="form-group">
                    <label><?php _e('JWT令牌'); ?></label>
                    <div class="input-group">
                        <input type="text" id="token-value" class="form-control" readonly>
                        <span class="input-group-btn">
                            <button class="btn btn-default" type="button" id="copy-token"><?php _e('复制'); ?></button>
                        </span>
                    </div>
                </div>
                <div class="form-group text-center">
                    <label><?php _e('二维码'); ?></label>
                    <div id="qrcode" style="padding: 10px;"></div>
                    <p class="help-block"><?php _e('扫描二维码可快速导入令牌到客户端'); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('关闭'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 1050;
    display: none;
    overflow: hidden;
    -webkit-overflow-scrolling: touch;
    outline: 0;
    background-color: rgba(0,0,0,0.5);
}

.modal-dialog {
    position: relative;
    width: auto;
    margin: 10px;
}

@media (min-width: 768px) {
    .modal-dialog {
        width: 600px;
        margin: 30px auto;
    }
}

.modal-content {
    position: relative;
    background-color: #fff;
    -webkit-background-clip: padding-box;
    background-clip: padding-box;
    border: 1px solid #999;
    border: 1px solid rgba(0,0,0,.2);
    border-radius: 6px;
    outline: 0;
    -webkit-box-shadow: 0 3px 9px rgba(0,0,0,.5);
    box-shadow: 0 3px 9px rgba(0,0,0,.5);
}

.modal-header {
    padding: 15px;
    border-bottom: 1px solid #e5e5e5;
}

.modal-title {
    margin: 0;
    line-height: 1.42857143;
}

.close {
    float: right;
    font-size: 21px;
    font-weight: 700;
    line-height: 1;
    color: #000;
    text-shadow: 0 1px 0 #fff;
    filter: alpha(opacity=20);
    opacity: .2;
    background: transparent;
    border: 0;
    cursor: pointer;
}

.modal-body {
    position: relative;
    padding: 15px;
}

.modal-footer {
    padding: 15px;
    text-align: right;
    border-top: 1px solid #e5e5e5;
}

.form-group {
    margin-bottom: 15px;
}

.input-group {
    position: relative;
    display: table;
    border-collapse: separate;
}

.input-group .form-control {
    position: relative;
    z-index: 2;
    float: left;
    width: 100%;
    margin-bottom: 0;
}

.input-group-btn {
    position: relative;
    font-size: 0;
    white-space: nowrap;
}

.input-group-btn > .btn {
    position: relative;
}

.btn {
    display: inline-block;
    padding: 6px 12px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    -ms-touch-action: manipulation;
    touch-action: manipulation;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    background-image: none;
    border: 1px solid transparent;
    border-radius: 4px;
}

.btn-default {
    color: #333;
    background-color: #fff;
    border-color: #ccc;
}

.btn-primary {
    color: #fff;
    background-color: #337ab7;
    border-color: #2e6da4;
}
</style>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
?>

<script src="<?php $options->adminUrl('js/qrcode.min.js'); ?>"></script>
<script>
$(document).ready(function() {
    // 查看令牌
    $('.view-token').on('click', function(e) {
        e.preventDefault();
        
        var url = $(this).attr('href');
        
        // 发送AJAX请求获取令牌详情
        $.get(url, function(data) {
            if (data.success) {
                $('#token-purpose').text(data.purpose);
                $('#token-value').val(data.token);
                
                // 显示模态框
                $('#token-modal').show();
                
                // 生成二维码
                $('#qrcode').empty();
                if (typeof QRCode !== 'undefined' && QRCode && typeof QRCode.CorrectLevel !== 'undefined') {
                    try {
                        var qrcode = new QRCode(document.getElementById("qrcode"), {
                            text: JSON.stringify(data.qrCodeInfo),
                            width: 200,
                            height: 200,
                            colorDark : "#000000",
                            colorLight : "#ffffff",
                            correctLevel : QRCode.CorrectLevel.H
                        });
                    } catch (e) {
                        console.error('生成二维码时出错:', e);
                        $('#qrcode-container').html('<p class="error-message">无法生成二维码，请刷新页面重试。</p>');
                    }
                } else {
                    $('#qrcode-container').html('<p class="error-message">二维码功能暂时不可用，请联系管理员。</p>');
                }
            } else {
                alert(data.message || '获取令牌信息失败');
            }
        }, 'json');
    });
    
    // 关闭模态框
    $('.modal .close, .modal .btn-default').on('click', function() {
        $('.modal').hide();
    });
    
    // 点击模态框外部关闭
    $(document).on('click', '.modal', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // 复制令牌
    $('#copy-token').on('click', function() {
        var copyText = document.getElementById("token-value");
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        
        // 尝试使用现代Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(copyText.value).then(() => {
                var originalText = $('#copy-token').text();
                $('#copy-token').text('已复制');
                setTimeout(() => {
                    $('#copy-token').text(originalText);
                }, 2000);
            }).catch(err => {
                console.error('复制失败: ', err);
                // 降级到传统方法
                document.execCommand("copy");
                var originalText = $('#copy-token').text();
                $('#copy-token').text('已复制');
                setTimeout(() => {
                    $('#copy-token').text(originalText);
                }, 2000);
            });
        } else {
            // 传统方法
            document.execCommand("copy");
            var originalText = $('#copy-token').text();
            $('#copy-token').text('已复制');
            setTimeout(() => {
                $('#copy-token').text(originalText);
            }, 2000);
        }
    });
});
</script>

<?php
\Typecho\Plugin::factory('admin/profile.php')->bottom();
include 'footer.php';
?>