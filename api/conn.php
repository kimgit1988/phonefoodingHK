<?php
		// 定义页面的输出字符集为 UTF-8
		header('Content-Type:text/html; charset=UTF-8');
        //session_start();
		
		// 连接数据库
		$con =  mysql_connect('localhost:3306','root','meal2018');
		mysql_select_db("food", $con);
		mysql_query("set character set 'utf8'");//读库 
		mysql_query("set names 'utf8'");//写库 
		if (!$con)
		{
		  die('Could not connect: ' . mysql_error());
		}
		
		// 设定时区 - 中国时区
		date_default_timezone_set('Asia/Shanghai');
?>