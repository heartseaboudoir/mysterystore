<?php
require_once 'PushMsg.php';
class Message {
	public function Message($biz_content) {
		header ( "Content-Type: text/xml;charset=GBK" );
		$UserInfo = $this->getNode ( $biz_content, "UserInfo" );
		$FromUserId = $this->getNode ( $biz_content, "FromUserId" );
		$AppId = $this->getNode ( $biz_content, "AppId" );
		$CreateTime = $this->getNode ( $biz_content, "CreateTime" );
		$MsgType = $this->getNode ( $biz_content, "MsgType" );
		$EventType = $this->getNode ( $biz_content, "EventType" );
		$AgreementId = $this->getNode ( $biz_content, "AgreementId" );
		$ActionParam = $this->getNode ( $biz_content, "ActionParam" );
		$AccountNo = $this->getNode ( $biz_content, "AccountNo" );
                
		$push = new \PushMsg ();
		// 收到用户发送的对话消息
		if ($MsgType == "text") {
			$text = $this->getNode ( $biz_content, "Text" );
                        
			$result = D('Addons://AlipayServer/AlipayServerKeyword')->get_content($text);
                        
                        if(!empty($result['content'])){
                            $text_msg = $push->mkTextMsg ( $result['content']);

                            // 发给这个关注的用户
                            $biz_content = $push->mkTextBizContent ( $FromUserId, $text_msg );

                            //$biz_content = iconv ( "UTF-8", "GBK//IGNORE", $biz_content );

                            // $return_msg = $push->sendMsgRequest ( $biz_content );
                            $return_msg = $push->sendRequest ( $biz_content );
                        }else{
                            $server_info = D('Addons://AlipayServer/AlipayServerConfig')->get_info();

                            if(!empty($server_info['config']['msgset']['default']['content'])){
                                $text_msg = $push->mkTextMsg ($server_info['config']['msgset']['default']['content'] );

                                // 发给这个关注的用户
                                $biz_content = $push->mkTextBizContent ( $FromUserId, $text_msg );

                                //$biz_content = iconv ( "UTF-8", "GBK//IGNORE", $biz_content );

                                // $return_msg = $push->sendMsgRequest ( $biz_content );
                                $return_msg = $push->sendRequest ( $biz_content );
                            }
                        }
		}elseif ($MsgType == "image") {
			/*
			$mediaId = $this->getNode ( $biz_content, "MediaId" );
			$format = $this->getNode ( $biz_content, "Format" );
			
			$biz_content = "{\"mediaId\":\"" . $mediaId . "\"}";
			
			$fileName = realpath ( "img" ) . "/$mediaId.$format";
			// 下载保存图片
			$push->downMediaRequest ( $biz_content, $fileName );
			
			
			$text_msg = $push->mkTextMsg ( "你好，图片已接收。" );
			
			// 发给这个关注的用户
			$biz_content = $push->mkTextBizContent ( $FromUserId, $text_msg );
			$biz_content = iconv ( "UTF-8", "GBK//IGNORE", $biz_content );
			// $return_msg = $push->sendMsgRequest ( $biz_content );
			$return_msg = $push->sendRequest ( $biz_content );
			*/
		}
		
		// 收到用户发送的关注消息
		if ($EventType == "follow") {
			// 处理关注消息
			// 一般情况下，可推送一条欢迎消息或使用指导的消息。
			// 如：
                        
			$server_info = D('Addons://AlipayServer/AlipayServerConfig')->get_info();
                        
                        if(!empty($server_info['config']['msgset']['subscribe']['content'])){
                            $text_msg = $push->mkTextMsg ($server_info['config']['msgset']['subscribe']['content'] );
                        
                            // 发给这个关注的用户
                            $biz_content = $push->mkTextBizContent ( $FromUserId, $text_msg );

                            //$biz_content = iconv ( "UTF-8", "GBK//IGNORE", $biz_content );

                            // $return_msg = $push->sendMsgRequest ( $biz_content );
                            $return_msg = $push->sendRequest ( $biz_content );
                        }
                        /*
			$image_text_msg1 = $push->mkImageTextMsg ( "标题，感谢关注", "描述", "http://m.taobao.com", "https://i.alipayobjects.com/e/201310/1H9ctsy9oN_src.jpg", "loginAuth" );
			$image_text_msg2 = $push->mkImageTextMsg ( "标题", "描述", "http://m.taobao.com", "https://i.alipayobjects.com/e/201310/1H9ctsy9oN_src.jpg", "loginAuth" );
			// 组装多条图文信息
			$image_text_msg = array (
					$image_text_msg1,
					$image_text_msg2 
			);
			// 发给这个关注的用户
			$biz_content = $push->mkImageTextBizContent ( $FromUserId, $image_text_msg );
			
			$return_msg = $push->sendRequest ( $biz_content );*/
                        
		} elseif ($EventType == "unfollow") {
			// 处理取消关注消息
		} elseif ($EventType == "enter") {
			
			// 处理进入消息，扫描二维码进入,获取二维码扫描传过来的参数
			
			$arr = json_decode ( $ActionParam );
			if ($arr != null) {
				
				$sceneId = $arr->scene->sceneId;
				// 这里可以根据定义场景ID时指定的规则，来处理对应事件。
				// 如：跳转到某个页面，或记录从什么来源(哪种宣传方式)来关注的本服务窗
			}
			// 处理关注消息
			// 一般情况下，可推送一条欢迎消息或使用指导的消息。
			// 如：
			//$image_text_msg1 = $push->mkImageTextMsg ( "标题，进入服务窗", "描述：进入服务窗", "http://m.taobao.com", "", "loginAuth" );
			// $image_text_msg2 = $push->mkImageTextMsg ( "标题", "描述", "http://m.taobao.com", "https://i.alipayobjects.com/e/201310/1H9ctsy9oN_src.jpg", "loginAuth" );
			// 组装多条图文信息
//			$image_text_msg = array (
//					$image_text_msg1 
//			// $image_text_msg2
//						);
//			
//			// 发给这个关注的用户
//			$biz_content = $push->mkImageTextBizContent ( $FromUserId, $image_text_msg );
//			
//			$return_msg = $push->sendRequest ( $biz_content );
			// 日志记录
		} elseif ($EventType == "click") {
			$result = D('Addons://AlipayServer/AlipayServerKeyword')->get_content($ActionParam);
                        if(!empty($result['content'])){
                            $text_msg = $push->mkTextMsg ( $result['content']);

                            // 发给这个关注的用户
                            $biz_content = $push->mkTextBizContent ( $FromUserId, $text_msg );

                            $return_msg = $push->sendRequest ( $biz_content );
                        }
			// 处理菜单点击的消息
			/*
			// 在服务窗后台配置一个菜单，菜单类型为调用服务，菜单参数为sendmsg，用户点击次菜单后，就会调用到这里
			if ($ActionParam == "sendmsg") {
				$image_text_msg1 = $push->mkImageTextMsg ( "标题，发送消息测试", "描述：发送消息测试", "http://m.taobao.com", "", "loginAuth" );
				// 组装多条图文信息
				$image_text_msg = array (
						$image_text_msg1 
				);
				
				// 发给这个关注的用户
				$biz_content = $push->mkImageTextBizContent ( $FromUserId, $image_text_msg );				
				$return_msg = $push->sendRequest ( $biz_content );
				// 日志记录
			}
			
			//服务窗顶部添加会员号点击后，直接返回XML
			elseif ($ActionParam == "authentication"){
//				$redirect_url = "http://m.taobao.com";
//				$biz_content = "<XML><ToUserId><![CDATA[".$FromUserId."]]></ToUserId><AgreementId><![CDATA[]]></AgreementId><AppId><![CDATA[".$AppId."]]></AppId><CreateTime>".time()."</CreateTime><MsgType><![CDATA[image-text]]></MsgType><ArticleCount>1</ArticleCount><Articles><Item><Title><![CDATA[]]></Title><Desc><![CDATA[]]></Desc><ImageUrl><![CDATA[]]></ImageUrl><Url><![CDATA[".$redirect_url."]]></Url></Item></Articles><Push><![CDATA[false]]></Push></XML>";
				//echo $biz_content;
				exit();
			}*/
		}
		
		// 给支付宝返回ACK回应消息，不然支付宝会再次重试发送消息,再调用此方法之前，不要打印输出任何内容
                
		echo self::mkAckMsg ( $FromUserId );
		exit ();
	}
	public function mkAckMsg($toUserId) {
                require_once 'AlipaySign.php';
		$as = new \AlipaySign ();
		require 'config.php';
		$response_xml = "<XML><ToUserId><![CDATA[" . $toUserId . "]]></ToUserId><AppId><![CDATA[" . $config ['app_id'] . "]]></AppId><CreateTime>" . time () . "</CreateTime><MsgType><![CDATA[ack]]></MsgType></XML>";
		
		$return_xml = $as->sign_response ( $response_xml, $config ['charset'], $config ['merchant_private_key_file'] );
                
                M('Test')->add(array('val' => $return_xml,'type' => 'Message', 'create_time' => date('Y-m-d H:i:s')));
		return $return_xml;
	}
	
	/**
	 * 直接获取xml中某个结点的内容
	 *
	 * @param unknown $xml        	
	 * @param unknown $node        	
	 */
	public function getNode($xml, $node) {
		$xml = "<?xml version=\"1.0\" encoding=\"GBK\"?>" . $xml;
		$dom = new DOMDocument ( "1.0", "GBK" );
		$dom->loadXML ( $xml );
		$event_type = $dom->getElementsByTagName ( $node );
		return $event_type->item ( 0 )->nodeValue;
	}
}