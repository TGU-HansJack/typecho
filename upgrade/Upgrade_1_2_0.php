<?php

class Upgrade_1_2_0 implements Typecho_Upgrade_Interface
{
    /**
     * 升级至1.2.0的SQL语句
     * 
     * @access public
     * @param Typecho_Db $db 数据库对象
     * @param Typecho_Widget $options 全局信息组件
     * @return void
     */
    public static function upgrade($db, $options)
    {
        // 创建用户令牌表
        $adapterName = $db->getAdapterName();
        $prefix = $db->getPrefix();
        
        if (false !== strpos($adapterName, 'Mysql')) {
            // MySQL适配器
            $db->query("CREATE TABLE  `{$prefix}user_tokens` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userId` int(10) unsigned NOT NULL,
  `token` text NOT NULL,
  `purpose` varchar(255) default 'API Access',
  `created` int(10) unsigned default '0',
  `expired` int(10) unsigned default '0',
  PRIMARY KEY  (`id`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
        } else if (false !== strpos($adapterName, 'Pgsql')) {
            // Pgsql适配器
            $db->query("CREATE TABLE \"{$prefix}user_tokens\" (
  \"id\" SERIAL,
  \"userId\" INT NOT NULL,
  \"token\" TEXT NOT NULL,
  \"purpose\" VARCHAR(255) DEFAULT 'API Access',
  \"created\" INT DEFAULT 0,
  \"expired\" INT DEFAULT 0,
  PRIMARY KEY  (\"id\")
);");
            
            $db->query("CREATE INDEX \"{$prefix}user_tokens_userId\" ON \"{$prefix}user_tokens\" (\"userId\");");
        } else if (false !== strpos($adapterName, 'SQLite')) {
            // SQLite适配器
            $db->query("CREATE TABLE \"{$prefix}user_tokens\" (
  \"id\" INTEGER NOT NULL PRIMARY KEY,
  \"userId\" INTEGER NOT NULL,
  \"token\" TEXT NOT NULL,
  \"purpose\" VARCHAR(255) DEFAULT 'API Access',
  \"created\" INTEGER DEFAULT 0,
  \"expired\" INTEGER DEFAULT 0
);");
            
            $db->query("CREATE INDEX \"{$prefix}user_tokens_userId\" ON \"{$prefix}user_tokens\" (\"userId\");");
            
            $db->query("CREATE TRIGGER \"{$prefix}user_tokens_autoincrement\" 
            AFTER INSERT ON \"{$prefix}user_tokens\"
            FOR EACH ROW 
            BEGIN 
                UPDATE \"{$prefix}user_tokens\" SET \"id\" = (SELECT MAX(\"id\") FROM \"{$prefix}user_tokens\") WHERE \"rowid\" = NEW.\"rowid\" AND \"id\" IS NULL; 
            END;");
        }
    }
    
    /**
     * 获取升级到1.2.0的描述信息
     * 
     * @access public
     * @return string
     */
    public static function describe()
    {
        return '增加用户令牌表以支持多令牌管理';
    }
}