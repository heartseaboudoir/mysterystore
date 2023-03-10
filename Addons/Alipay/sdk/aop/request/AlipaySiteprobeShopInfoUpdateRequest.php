<?php
/**
 * ALIPAY API: alipay.siteprobe.shop.info.update request
 *
 * @author auto create
 * @since 1.0, 2014-12-18 09:04:22
 */
class AlipaySiteprobeShopInfoUpdateRequest
{
	/** 
	 * Json格式的业务参数，其中
shop_id ：店铺Id（必须）
shop_notice ：店铺公告
adv_type ：推广类型，可以为h5或者public(服务窗）
h5_rul ：广告页URL（可选，如果未绑定则不包含该字段）
	 **/
	private $bizContent;

	private $apiParas = array();
	private $terminalType;
	private $terminalInfo;
	private $prodCode;
	private $apiVersion="1.0";
	
	public function setBizContent($bizContent)
	{
		$this->bizContent = $bizContent;
		$this->apiParas["biz_content"] = $bizContent;
	}

	public function getBizContent()
	{
		return $this->bizContent;
	}

	public function getApiMethodName()
	{
		return "alipay.siteprobe.shop.info.update";
	}

	public function getApiParas()
	{
		return $this->apiParas;
	}

	public function getTerminalType()
	{
		return $this->terminalType;
	}

	public function setTerminalType($terminalType)
	{
		$this->terminalType = $terminalType;
	}

	public function getTerminalInfo()
	{
		return $this->terminalInfo;
	}

	public function setTerminalInfo($terminalInfo)
	{
		$this->terminalInfo = $terminalInfo;
	}

	public function getProdCode()
	{
		return $this->prodCode;
	}

	public function setProdCode($prodCode)
	{
		$this->prodCode = $prodCode;
	}

	public function setApiVersion($apiVersion)
	{
		$this->apiVersion=$apiVersion;
	}

	public function getApiVersion()
	{
		return $this->apiVersion;
	}

}
