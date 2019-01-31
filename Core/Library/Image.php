<?php
namespace Core\Library;

class Image
{
	/**
	 * 位置常量，添加水印时用到
	 */
	const POS_LEFT_TOP = 1;//左上角
	
	const POS_TOP = 2;//上边
	
	const POS_RIGHT_TOP = 3;//右上角
	
	const POS_RIGHT = 4;//右边
	
	const POS_RIGHT_BOTTOM = 5;//右下角
	
	const POS_BOTTOM = 6;//下边
	
	const POS_LEFT_BOTTOM = 7;//左下角
	
	const POS_LEFT = 8;//左边
	
	const POS_CENTER = 9;//中间
	
	
	/**
	 * 生成缩略图方式常量
	 */
	const THUMB_FIT_1 = 1;//进行裁切，多余部分切掉，也就是不够时以较小的一边为准

	const THUMB_FIT_2 = 2;//裁切时就必须规定要保留哪一部分，1为最左（最顶）部分，2为中间部分，3为最右（最下边）部分
	
	const THUMB_FIT_3 = 3;
	
	const THUMB_FILL = 4;//进行扩展，以较大的一边为准，也就是不够时将不够的部分背景填充，选择该模式可以设定背景色及背景透明度
	
	/**
	 * flip翻转方式
	 */
	const FLIP_X = 1;//X轴翻转
	
	const FLIP_Y = 2;//Y轴翻转
	
	
	const OFFSET_IMG = 1;//求图片的Offset
	
	const OFFSET_TEXT = 2;
	
	private $tmpFile;//临时文件
	
	private $dstFile;//目标文件，移动之后的地址
	
	private $encodeFile;//编码过的文件名
	
	private $info;//文件信息
	
	private $img;//画布资源
	
	private $format;//图片格式，用于获得相应的函数
	
	private $ext;//后缀。格式不一定是后缀，如格式是jpeg一般后缀为jpg
	
	private $isUploaded;//是否是上传的图片
	
	private $doHandle = FALSE;//是否进行了旋转、裁切等处理
	
	public function __construct($tmpFile)
	{
		if (!is_string($tmpFile))
		{
			throw new Exception(__CLASS__." __construct function Only accecpt a file name, it's a string not an array or other");
			exit;
		}
		$encodeFile = $this->encodeFile = self::_encodeFile($tmpFile);
		if (is_file($encodeFile))
		{
			$ext = pathinfo($tmpFile, PATHINFO_EXTENSION);//pathinfo无需编码
			if ($ext === 'tmp' && is_uploaded_file($tmpFile))
			{
				$this->isUploaded = TRUE;
				$this->tmpFile = $tmpFile;
				$this->_getInfo($tmpFile);
				$this->_createImgResource($tmpFile);
			}
			else
			{
				$this->isUploaded = FALSE;
				$this->dstFile = $tmpFile;
				$this->_getInfo($encodeFile);
				$this->_createImgResource($encodeFile);
			}
			
		}
		else 
		{
			throw new Exception("Not a file: $tmpFile");
		}
	}
	
	/**
	 * 裁切图片
	 */
	public function crop($dx, $dy, $width, $height)
	{
		$width = max(0, min($width, imagesx($this->img) - $dx));
		$height = max(0, min($height, imagesy($this->img) - $dy));
		$newImg = imagecreatetruecolor($width, $height);
		imagecopyresampled($newImg, $this->img, 0, 0, $dx, $dy, $width, $height, $width, $height);
		imagedestroy($this->img);
		$this->img = $newImg;
		$this->doHandle = TRUE;
		return $this;
	}
	
	/**
	 * 图片旋转。
	 */
	public function rotate($angle, $color = array(255, 255, 255, 0), $ignore_transparent = 0)
	{
		$transColor = imagecolorallocatealpha($this->img, $color[0], $color[1], $color[2], self::_getAlpha($color[3]));
		$newImg = imagerotate($this->img, $angle, $transColor, $ignore_transparent);//rotate返回的是旋转后的图像资源
		imagedestroy($this->img);
		$this->img = $newImg;
		imagesavealpha($this->img, TRUE);
		$this->doHandle = TRUE;
		return $this;
	}
	
	/**
	 * 图像翻转
	 */
	public function flip($flipType = self::FLIP_Y)
	{
		$width = imagesx($this->img);
		$height = imagesy($this->img);
		$newImg = imagecreatetruecolor($width, $height);
		if ($flipType == self::FLIP_X)
		{
			for ($y = 0; $y < $height; $y++)
			{
				imagecopy($newImg, $this->img, 0, $height - $y - 1, 0, $y, $width, 1);
			}
		}
		elseif ($flipType == self::FLIP_Y)
		{
			for ($x = 0; $x < $width; $x++)
			{
				imagecopy($newImg, $this->img, $width - $x - 1, 0, $x, 0, 1, $height);
			}
		}
		else 
		{
			throw new Exception("error params of flip()");
			return FALSE;
		}
		imagedestroy($this->img);
		$this->img = $newImg;
		$this->doHandle = TRUE;
		return $this;
	}
	
	/**
	 * 图像缩放（缩略图，放大或缩小）
	 */
	public function thumb($width, $height, $thumbType = self::THUMB_FIT_2, $color = array(255, 255, 255, 0))
	{
		$ori_w = imagesx($this->img);//画面图像宽高
		$ori_h = imagesy($this->img);
		$scale_w = $ori_w/$width;
		$scale_h = $ori_h/$height;
		$newImg = imagecreatetruecolor($width, $height);
		
		if ($thumbType != self::THUMB_FILL)
		{
			
			if ($scale_w >= $scale_h)
			{
				//宽度缩放系数大，以高度为准
				$src_w = $scale_h*$width;//更改源图片的宽度，目标宽高是定了的
				$src_h = $ori_h;
				
				switch ($thumbType)
				{
					case self::THUMB_FIT_1:
						$dst_x = $dst_y = $src_x = $src_y = 0;
						break;
					case self::THUMB_FIT_2:
						$src_x = ($ori_w - $src_w)/2;
						$src_y = 0;
						$dst_x = $dst_y = 0;
						break;
					case self::THUMB_FIT_3:
						$src_x = $ori_w - $src_w;
						$src_y = 0;
						$dst_x = $dst_y = 0;
						break;
					default:
						throw new Exception("invalid param thumbType: $thumbType");
						return FALSE;
				}
			}
			else
			{
				$src_h = $scale_w*$height;
				$src_w = $ori_w;
				
				switch ($thumbType)
				{
					case self::THUMB_FIT_1:
						$dst_x = $dst_y = $src_x = $src_y = 0;
						break;
					case self::THUMB_FIT_2:
						$src_y = ($ori_h - $src_h)/2;
						$src_x = 0;
						$dst_x = $dst_y = 0;
						break;
					case self::THUMB_FIT_3:
						$src_y = $ori_h - $src_h;
						$src_x = 0;
						$dst_x = $dst_y = 0;
						break;
					default:
						throw new Exception("invalid param thumbType: $thumbType");
						return FALSE;
				}
			}
			imagecopyresampled($newImg, $this->img, $dst_x, $dst_y, $src_x, $src_y, $width, $height, $src_w, $src_h);
		}
		else
		{
			$transColor = imagecolorallocatealpha($newImg, $color[0], $color[1], $color[2], self::_getAlpha($color[3]));
			imagefill($newImg, 0, 0, $transColor);
			imagesavealpha($newImg, TRUE);
			if ($scale_w >= $scale_h)
			{
				//宽比高大
				$src_w = $ori_w;
				$src_h = $height*$scale_w;
				$thumb_true_w = $width;
				$thumb_true_h = $ori_h/$scale_w;
				$dst_x = 0;
				$dst_y = ($height - $thumb_true_h)/2;
				$src_x = $src_y = 0;
				
				$scaleImg = imagecreatetruecolor($thumb_true_w, $thumb_true_h);//获得实际缩小后的图
			}
			else 
			{
				//高比宽要小
				$src_h = $ori_h;
				$src_w = $width*$scale_h;
				$thumb_true_w = $ori_w/$scale_h;//有效图片宽高
				$thumb_true_h = $height;
				$dst_x = ($width - $thumb_true_w)/2;
				$dst_y = 0;
				$src_x = $src_y = 0;
				
				$scaleImg = imagecreatetruecolor($thumb_true_w, $thumb_true_h);//获得实际缩小后的图
			}
			//先缩放，再合并到背景上
			imagecopyresampled($scaleImg, $this->img, 0, 0, 0, 0, $thumb_true_w, $thumb_true_h, $ori_w, $ori_h);
			imagecopymerge($newImg, $scaleImg, $dst_x, $dst_y, $src_x, $src_y, $thumb_true_w, $thumb_true_h, 100);
		}
		
		imagedestroy($this->img);
		$this->img = $newImg;
		$this->doHandle = TRUE;
		return $this;
	}
	
	public function textMask($text, $fontSize = 16, array $color = array(0, 0, 0, 100), $position = self::POS_RIGHT_BOTTOM, $offset_x = 20, $offset_y = 20, $fontFile = '')
	{
		if ($fontFile === '')
		{
			$fontFile = __DIR__.DIRECTORY_SEPARATOR.'msyh.ttc';
		}
		$encodeFontFile = self::_encodeFile($fontFile);
		if (!file_exists($encodeFontFile))
		{
			throw new Exception("fontFile not exists: $fontFile");
			return FALSE;
		}
		
		switch ($position)
		{
			case self::POS_LEFT_TOP:
			case self::POS_TOP:
			case self::POS_RIGHT_TOP:
			case self::POS_RIGHT_BOTTOM:
			case self::POS_BOTTOM:
			case self::POS_LEFT_BOTTOM:
			case self::POS_CENTER:
				$angle = 0;
				$rect = imagettfbbox($fontSize, 0, $fontFile, $text);//0角度的值，除左右外都是0角度
				break;
			case self::POS_RIGHT:
			case self::POS_LEFT:
				$angle = 90;//渲染字体的角度
				$rect = imagettfbbox($fontSize, 90, $encodeFontFile, $text);//右边是顺时针90度？
				break;
			default:
				throw new Exception("error position: $position");
				return FALSE;
		}
		// echo 'rect:<br/>';
		// self::dump($rect);
		$text_w = abs($rect[2] - $rect[6]);
		$text_h = abs($rect[3] - $rect[7]);
		// echo "text_w: $text_w, text_h: $text_h </br>";
		$offset = $this->_getOffset($position, $text_w, $text_h, $offset_x, $offset_y);
		// echo 'offset:<br/>';
		//self::dump($offset);
		$textColor = imagecolorallocatealpha($this->img, $color[0], $color[1], $color[2], self::_getAlpha($color[3]));//字体颜色，有透明度
		imagettftext($this->img, $fontSize, $angle, $offset[0], $offset[1] + $text_h, $textColor, $encodeFontFile, $text);
		$this->doHandle = TRUE;
		return $this;
	}

	public function imageMask($srcImg, $opacity = 100, $position = self::POS_RIGHT_BOTTOM, $offset_x = 20, $offset_y = 20)
	{
		$encodeSrcImage = self::_encodeFile($srcImg);
		if (!file_exists($encodeSrcImage))
		{
			throw new Exception("mask image not exists: $srcImg");
			return FALSE;
		}
		$info = self::_getInfo($encodeSrcImage, TRUE);
		$maskImg = call_user_func('imagecreatefrom'.$info['format'], $encodeSrcImage);
		$mask_w = imagesx($maskImg);
		$mask_h = imagesy($maskImg);
		$im_w = imagesx($this->img);
		$im_h = imagesy($this->img);
		$offset = self::_getOffset($position, $mask_w, $mask_h, $offset_x, $offset_y, self::OFFSET_IMG);
		imagecopymerge($this->img, $maskImg, $offset[0], $offset[1], 0, 0, $mask_w, $mask_h, $opacity);
		$this->doHandle = TRUE;
		return $this;
	}
	
	/**
	 * 保存图片到某位置
	 */
	public function save($targetFile)
	{
		if (empty($targetFile) || !is_string($targetFile))
		{
			throw new Exception('save() param must be a string');
			return FALSE;
		}
		$dir = dirname($targetFile);
		self::_mkdir($dir);
		$saveFile = $this->_getName($targetFile, TRUE);
		if ($this->isUploaded && empty($this->dstFile) && !$this->doHandle)
		{
			$move = move_uploaded_file($this->tmpFile, $saveFile);
			if ($move === FALSE)
			{
				throw new Exception("move file:".$this->tmpFile." to : $saveFile failed");
				return FALSE;
			}
			else 
			{
				$this->dstFile = $saveFile;
				$this->isUploaded = FALSE;
			}
		}
		else 
		{
			//判断一下是否有作缩略裁切等操作，如没有复制更好，能保持较高品质的图片
			if ($this->format === 'jpeg')
			{
				$outPutImg = imagejpeg($this->img, $saveFile, 100);//100%质量
			}
			else
			{
				$outPutImg = call_user_func('image'.$this->format, $this->img, $saveFile);//一样需要编码一下文件
			}
			if (!$outPutImg)
			{
				throw new Exception("image".$this->format."() execute failed. targetFile: $saveFile");
				return FALSE;
			}
		}
		$this->_createImgResource($saveFile);//以当前最新重新生成一个
		return $this;
	}
	
	/**
	 * Windows系统下中文转换一下编码
	 */
	private static function _encodeFile($fileName)
	{
		if (strpos(PHP_OS, 'WINNT') !== FALSE && preg_match('/[^\x00-\x80]/', $fileName))
		{
			return iconv('UTF-8', 'GB2312', $fileName);
		}
		else
		{
			return $fileName;
		}
	}

	private static function _getAlpha($alpha)
	{
		$alpha = min(intval($alpha), 100);//值为[0,100]
		$alpha = max($alpha, 0);
		$alpha = 100 - $alpha;
		$result = (127/100) * $alpha;
		$result = ceil($result);
		return $result > 127 ? 127 : $result;
	}
	
	/**
	 * 获得目标水印离图像左上角距离。offset_x是水平方向偏移，offset_y是垂直方向偏移
	 */
	private function _getOffset($position, $dst_w, $dst_h, $offset_x, $offset_y, $offsetType = self::OFFSET_TEXT)
	{
		$src_w = imagesx($this->img);
		$src_h = imagesy($this->img);//画布宽高
		$result = '';
		switch ($position)
		{
			case self::POS_LEFT_TOP:
				if ($dst_w + $offset_x > $src_w || $dst_h + $offset_y > $src_h)
				{
					throw new Exception("mask has overflow original image!--POS: ".$position);
					exit;
				}
				$result = array($offset_x, $offset_y);
				break;
			case self::POS_TOP:
				if ($dst_w > $src_w || $dst_h + $offset_x > $src_h)
				{
					throw new Exception("mask has overflow original image!--POS: ".$position);
					exit;
				}
				$result = array(($src_w - $dst_w)/2, $offset_y);
				break;
			case self::POS_RIGHT_TOP:
				if ($dst_w + $offset_x > $src_w || $dst_h + $offset_y > $src_h)
				{
					throw new Exception("mask has overflow original image!--POS: ".$position);
					exit;
				}
				$result = array($src_w - $dst_w - $offset_x, $offset_y);
				break;
			case self::POS_RIGHT:
				if ($offsetType == self::OFFSET_TEXT)
				{
					if ($offset_x > $src_w || $dst_h > $src_h)
					{
						throw new Exception("mask has overflow original image!--POS: ".$position);
						exit;
					}
					$result = array($src_w - $offset_x, ($src_h - $dst_h)/2);
				}
				elseif ($offsetType == self::OFFSET_IMG)
				{
					if ($offset_x + $dst_w > $src_w || $dst_h > $src_h)
					{
						throw new Exception("imageMask has overflow original image!--POS: ".$position);
						exit;
					}
					$result = array($src_w - $offset_x - $dst_w, ($src_h - $dst_h)/2);
				}
				else
				{
					throw new Exception("eror offset type: ".$offsetType);
					exit;
				}
				
				break;
			case self::POS_RIGHT_BOTTOM:
				if ($dst_w + $offset_x > $src_w || $dst_h + $offset_y > $src_h)
				{
					throw new Exception("mask has overflow original image!--POS: ".$position);
					exit;
				}
				$result = array($src_w - $dst_w - $offset_x, $src_h - $dst_h - $offset_y);
				break;
			case self::POS_BOTTOM:
				if ($dst_w > $src_w || $dst_h + $offset_y > $src_h)
				{
					throw new Exception("mask has overflow original image!--POS: ".$position);
					exit;
				}
				$result = array(($src_w - $dst_w)/2, $src_h - $dst_h - $offset_y);
				break;
			case self::POS_LEFT_BOTTOM:
				if ($dst_w + $offset_x > $src_w || $dst_h + $offset_y > $src_h)
				{
					throw new Exception("mask has overflow original image!--POS: ".$position);
					exit;
				}
				$result = array($offset_x, $src_h - $dst_h - $offset_y);
				break;
			case self::POS_LEFT:
				if ($offsetType == self::OFFSET_TEXT)
				{
					if ($dst_w + $offset_x > $src_w || $dst_h > $src_h)
					{
						throw new Exception("mask has overflow original image!--POS: ".$position);
						exit;
					}
					$result = array($offset_x + $dst_w, ($src_h - $dst_h)/2);
				}
				elseif ($offsetType == self::OFFSET_IMG)
				{
					if ($dst_w + $offset_x > $src_w || $dst_h > $src_h)
					{
						throw new Exception("imageMask has overflow original image!--POS: ".$position);
						exit;
					}
					$result = array($offset_x, ($src_h - $dst_h)/2);
				}
				
				break;
			case self::POS_CENTER:
				if ($dst_w > $src_w || $dst_h > $src_h)
				{
					throw new Exception("mask has overflow original image!--POS: ".$position);
					exit;
				}
				$result = array(($src_w - $dst_w)/2, ($src_h - $dst_h)/2);
				break;
			default:
				throw new Exception("error POS_XXX constant: ".$position);
				exit;
		}
		return $result;	
	}
	
	
	private static function _mkdir($dir)
	{
		if (!is_dir($dir))
		{
			$mkdir = mkdir($dir, 0777, TRUE);
			if ($mkdir === FALSE)
			{
				throw new Exception("can't mkdir: $dir");
				exit;
			}
		}
	}
	
	private function _getInfo($fileName, $return = FALSE)
	{
		$info = getimagesize($fileName);
		if ($info === FALSE)
		{
			throw new Exception("Not an image: $fileName");
			exit;
		}
		switch ($info[2])
		{
			case 1:
				$format = 'gif';
				$ext = '.gif';
				break;
			case 2:
				$format = 'jpeg';
				$ext = '.jpg';
				break;
			case 3:
				$format = 'png';
				$ext = '.png';
				break;
			default:
				throw new Exception('不支持的图片类型: '.image_type_to_mime_type($info[2]));
				exit;
		}
		$info['format'] = $format;
		$info['ext'] = $ext;
		if ($return)
		{
			return $info;
		}
		else 
		{
			$this->format = $format;
			$this->ext = $ext;
			$this->info = $info;
		}
	}
	
	private function _createImgResource($imgFile)
	{
		if (!empty($this->img))
		{
			imagedestroy($this->img);
		}
		$createImg = call_user_func('imagecreatefrom'.$this->format, $imgFile);
		if ($createImg !== FALSE)
		{
			$this->img = $createImg;
		}
		else
		{
			throw new Exception('创建画布资源失败');
			exit;
		}
	}
	
	/**
	 * 获得保存的文件名(包含路径)
	 */
	private function _getName($targetFile, $encode = FALSE)
	{
		$targetFile = rtrim($targetFile, '/');
		$dir = dirname($targetFile);
		$lastPos = strrpos($targetFile, '/');//路径分割符统一为/
		$baseName = substr($targetFile, $lastPos);//基本名称
		$lastDotPos = strrpos($baseName, '.');
		if ($lastDotPos !== FALSE)
		{
			//有点
			$pureName = substr($baseName, 0, $lastDotPos);//
			$ext = substr($baseName, $lastDotPos);
			if ($ext !== $this->ext)
			{
				$baseName .= $this->ext;//不是后缀，添加上
			}
		}
		else 
		{
			//没点，肯定无后缀
			$baseName .= $this->ext;
		}
		$saveFile = $dir.DIRECTORY_SEPARATOR.$baseName;
		return $encode ? self::_encodeFile($saveFile) : $saveFile;
	}
	
	/**
	 * 返回图片信息，以方便计算一些数据进行裁切等
	 */
	public function getInfo()
	{
		return $this->info;
	}
	
	private static function dump($var)
	{
		echo '<pre>';
		var_dump($var);
		echo '</pre>';
	}
}