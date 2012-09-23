<?php
/*
	analyzes image and reads characters on it
	
	characters should be already analyzed and inserted into database ,
	
	you can analyze and insert characters in database also
*/


class Image2String {
	
	private $lineTop = 0;
	private $lineBottom = 0;
	private $lineLeft = 0;
	private $lineRight = 0;
	private $charTop = 0;
	private $charBottom = 0;
	private $left = 0;
	private $right = 0;
	private $imageW = 0;
	private $imageH = 0;
	private $type = 0;
	private $image;
	private $tinyImage;
	private $imageString = "";
	private $lastFoundLeft = 0;
	private $totalChars = 0;
	
	
	
	
	// class constructor , takes image path and starts process
	
	function __construct($image) {
		list($this->imageW,$this->imageH,$this->type) = getimagesize($image);
		if($this->type == 2) {
			$this->image = imagecreatefromjpeg($image);	
		} else if($this->type == 3) {
			$this->image = imagecreatefrompng($image);	
		} else {
			die("Unknown Image Type");
		}
		imagefilter($this->image,IMG_FILTER_GRAYSCALE,IMG_FILTER_PIXELATE);
	
		$this->getLineTop();
		
		
	}

	// returns read characters
	public function getImageString(){
		return $this->imageString;	
	}
	
	// gets the top pixel of the line
	private function getLineTop(){
		if(strlen($this->imageString) > 0){
			$this->imageString .= "\n";	
		}
		while(1){
			if(10000000 <= imagecolorat($this->image,$this->lineLeft,$this->lineTop)){
				if($this->lineLeft == $this->imageW -1 && $this->lineTop == $this->imageH - 1)
				{
					return;
				}
				if($this->lineLeft != $this->imageW - 1){
					$this->lineLeft++;	
				} else {
					$this->lineLeft = 0;
					$this->lineTop++;	
				}	
			} else {		
				$this->lineLeft = 0;
				$this->getLineBottom();
				return;
			}	
		}
	}
	
	
	// gets the bottom pixel of the line
	private function getLineBottom(){
		$stop = $this->lineTop + 1;
		$sx = 0;
		while(1){
			if(10000000 <= imagecolorat($this->image,$sx,$stop)){
				if($sx == $this->imageW - 1){
					$this->lineBottom = $stop;
					$this->getLeft();
					return;
				} else {
					$sx++;	
				}
			} else {
				if($stop < $this->imageH - 1){
					$stop++;
					$sx = 0;
				} else {
					$this->lineBottom =  $this->imageH;
					$this->getLeft();
					return;
				}
			}
		}
	}
	
	
	// gets the left pixel of line
	private function getLeft(){
		$imageTop = $this->lineTop;
		$sx = $this->lineLeft;
		while(1){
			if($this->lastFoundLeft == 8){
				$this->imageString .= " ";
				$this->lastFoundLeft = 0;
			}
			if(10000000 < imagecolorat($this->image,$sx,$imageTop)){
				if($imageTop == $this->lineBottom && $sx == $this->imageW -1){
					if($this->lineBottom < $this->imageH - 1){
						$this->lineTop = $this->lineBottom;
						$this->lineLeft = 0;
						$this->getLineTop();						
						return;
					} else {
						return;	
					}
				}
				if($imageTop == $this->lineBottom){
					$sx++;
					$this->lastFoundLeft++;
					$imageTop = $this->lineTop;	
				} else {
					$imageTop++;
				}
			} else {
				$this->left = $sx;
				$this->lastFoundLeft = 0;	
				$this->getRight();
				return;
				}
		}
			
	}
	
	
	//gets right pixel of character
	private function getRight(){
		$imageTop = $this->lineTop;
		$sx = $this->left;
		while(1){
			if(10000000 > imagecolorat($this->image,$sx,$imageTop)){
				if($sx == $this->imageW -1 && $imageTop == $this->lineBottom){
					$this->right = $sx;
					$this->trimChar();
					return;		
				} else {
					$sx++;
					$imageTop = $this->lineTop;	
				}
			} else {
				if($sx != $this->imageW -1 && $imageTop != $this->lineBottom){
					$imageTop++;
				} else {
					$this->right = $sx;
					$this->trimChar();
					return;	
				}
			}
		}
	}
	
	// trims character image to recognize
	private function trimChar(){
		$sx = $this->left;
		$sy = $this->lineTop;
		while(1){
			if(10000000 < imagecolorat($this->image,$sx,$sy)){
				if($sx == $this->right){
					$sx = $this->left;
					$sy++;	
				} else {
					$sx++;	
				}
			} else {
				$this->charTop = $sy;
				$sx = $this->left;
				$sy = $this->lineBottom;
				while(1){
					if(10000000 < imagecolorat($this->image,$sx,$sy)){
						if($sx == $this->right){
							$sx = $this->left;
							$sy--;	
						} else {
							$sx++;	
						}
					} else {
						$this->charBottom = $sy+1;
						$s_w = $this->right - $this->left;
						$s_h = $this->charBottom - $this->charTop;
						$this->tinyImage = imagecreatetruecolor(40, 40);
						imagecopyresampled($this->tinyImage,$this->image,0,0,$this->left, $this->charTop,40,40,$s_w,$s_h);
						//imagejpeg($this->tinyImage,'images/' . $this->totalChars . '.jpg');
						//$this->totalChars++;
						$this->getCharFromImage();
						imagedestroy($this->tinyImage);
						if($this->right < $this->imageW - 1){
							$this->lineLeft = $this->right;
							$this->getLeft();
						} else {
							if($this->lineBottom >= $this->imageH -2) {
							$this->lineTop = $this->lineBottom + 1;
							$this->getLineTop();
							}
						}
						return;	
					}
				}
			}
		}
		
		
	}

	// asks database to return closest character to recognized pixels
	private function getCharFromImage(){
		for($i = 0;$i <= 38;$i+=2){
			for($x = 0;$x<=38;$x+=2){
				$tp[] = imagecolorat($this->tinyImage,$x,$i) > 10000000 ? 0 : 1;
			}
		}	
	
$sql = "SELECT chr FROM chars
ORDER BY (IF(tp0 = " . $tp['0'] . ",1,0)+IF(tp1 = " . $tp['1'] . ",1,0)+IF(tp2 = " . $tp['2'] . ",1,0)+IF(tp3 = " . $tp['3'] . ",1,0)+IF(tp4 = " . $tp['4'] . ",1,0)+IF(tp5 = " . $tp['5'] . ",1,0)+IF(tp6 = " . $tp['6'] . ",1,0)+IF(tp7 = " . $tp['7'] . ",1,0)+IF(tp8 = " . $tp['8'] . ",1,0)+IF(tp9 = " . $tp['9'] . ",1,0)+IF(tp10 = " . $tp['10'] . ",1,0)+IF(tp11 = " . $tp['11'] . ",1,0)+IF(tp12 = " . $tp['12'] . ",1,0)+IF(tp13 = " . $tp['13'] . ",1,0)+IF(tp14 = " . $tp['14'] . ",1,0)+IF(tp15 = " . $tp['15'] . ",1,0)+IF(tp16 = " . $tp['16'] . ",1,0)+IF(tp17 = " . $tp['17'] . ",1,0)+IF(tp18 = " . $tp['18'] . ",1,0)+IF(tp19 = " . $tp['19'] . ",1,0)+IF(tp20 = " . $tp['20'] . ",1,0)+IF(tp21 = " . $tp['21'] . ",1,0)+IF(tp22 = " . $tp['22'] . ",1,0)+IF(tp23 = " . $tp['23'] . ",1,0)+IF(tp24 = " . $tp['24'] . ",1,0)+IF(tp25 = " . $tp['25'] . ",1,0)+IF(tp26 = " . $tp['26'] . ",1,0)+IF(tp27 = " . $tp['27'] . ",1,0)+IF(tp28 = " . $tp['28'] . ",1,0)+IF(tp29 = " . $tp['29'] . ",1,0)+IF(tp30 = " . $tp['30'] . ",1,0)+IF(tp31 = " . $tp['31'] . ",1,0)+IF(tp32 = " . $tp['32'] . ",1,0)+IF(tp33 = " . $tp['33'] . ",1,0)+IF(tp34 = " . $tp['34'] . ",1,0)+IF(tp35 = " . $tp['35'] . ",1,0)+IF(tp36 = " . $tp['36'] . ",1,0)+IF(tp37 = " . $tp['37'] . ",1,0)+IF(tp38 = " . $tp['38'] . ",1,0)+IF(tp39 = " . $tp['39'] . ",1,0)+IF(tp40 = " . $tp['40'] . ",1,0)+IF(tp41 = " . $tp['41'] . ",1,0)+IF(tp42 = " . $tp['42'] . ",1,0)+IF(tp43 = " . $tp['43'] . ",1,0)+IF(tp44 = " . $tp['44'] . ",1,0)+IF(tp45 = " . $tp['45'] . ",1,0)+IF(tp46 = " . $tp['46'] . ",1,0)+IF(tp47 = " . $tp['47'] . ",1,0)+IF(tp48 = " . $tp['48'] . ",1,0)+IF(tp49 = " . $tp['49'] . ",1,0)+IF(tp50 = " . $tp['50'] . ",1,0)+IF(tp51 = " . $tp['51'] . ",1,0)+IF(tp52 = " . $tp['52'] . ",1,0)+IF(tp53 = " . $tp['53'] . ",1,0)+IF(tp54 = " . $tp['54'] . ",1,0)+IF(tp55 = " . $tp['55'] . ",1,0)+IF(tp56 = " . $tp['56'] . ",1,0)+IF(tp57 = " . $tp['57'] . ",1,0)+IF(tp58 = " . $tp['58'] . ",1,0)+IF(tp59 = " . $tp['59'] . ",1,0)+IF(tp60 = " . $tp['60'] . ",1,0)+IF(tp61 = " . $tp['61'] . ",1,0)+IF(tp62 = " . $tp['62'] . ",1,0)+IF(tp63 = " . $tp['63'] . ",1,0)+IF(tp64 = " . $tp['64'] . ",1,0)+IF(tp65 = " . $tp['65'] . ",1,0)+IF(tp66 = " . $tp['66'] . ",1,0)+IF(tp67 = " . $tp['67'] . ",1,0)+IF(tp68 = " . $tp['68'] . ",1,0)+IF(tp69 = " . $tp['69'] . ",1,0)+IF(tp70 = " . $tp['70'] . ",1,0)+IF(tp71 = " . $tp['71'] . ",1,0)+IF(tp72 = " . $tp['72'] . ",1,0)+IF(tp73 = " . $tp['73'] . ",1,0)+IF(tp74 = " . $tp['74'] . ",1,0)+IF(tp75 = " . $tp['75'] . ",1,0)+IF(tp76 = " . $tp['76'] . ",1,0)+IF(tp77 = " . $tp['77'] . ",1,0)+IF(tp78 = " . $tp['78'] . ",1,0)+IF(tp79 = " . $tp['79'] . ",1,0)+IF(tp80 = " . $tp['80'] . ",1,0)+IF(tp81 = " . $tp['81'] . ",1,0)+IF(tp82 = " . $tp['82'] . ",1,0)+IF(tp83 = " . $tp['83'] . ",1,0)+IF(tp84 = " . $tp['84'] . ",1,0)+IF(tp85 = " . $tp['85'] . ",1,0)+IF(tp86 = " . $tp['86'] . ",1,0)+IF(tp87 = " . $tp['87'] . ",1,0)+IF(tp88 = " . $tp['88'] . ",1,0)+IF(tp89 = " . $tp['89'] . ",1,0)+IF(tp90 = " . $tp['90'] . ",1,0)+IF(tp91 = " . $tp['91'] . ",1,0)+IF(tp92 = " . $tp['92'] . ",1,0)+IF(tp93 = " . $tp['93'] . ",1,0)+IF(tp94 = " . $tp['94'] . ",1,0)+IF(tp95 = " . $tp['95'] . ",1,0)+IF(tp96 = " . $tp['96'] . ",1,0)+IF(tp97 = " . $tp['97'] . ",1,0)+IF(tp98 = " . $tp['98'] . ",1,0)+IF(tp99 = " . $tp['99'] . ",1,0)+IF(tp100 = " . $tp['100'] . ",1,0)+IF(tp101 = " . $tp['101'] . ",1,0)+IF(tp102 = " . $tp['102'] . ",1,0)+IF(tp103 = " . $tp['103'] . ",1,0)+IF(tp104 = " . $tp['104'] . ",1,0)+IF(tp105 = " . $tp['105'] . ",1,0)+IF(tp106 = " . $tp['106'] . ",1,0)+IF(tp107 = " . $tp['107'] . ",1,0)+IF(tp108 = " . $tp['108'] . ",1,0)+IF(tp109 = " . $tp['109'] . ",1,0)+IF(tp110 = " . $tp['110'] . ",1,0)+IF(tp111 = " . $tp['111'] . ",1,0)+IF(tp112 = " . $tp['112'] . ",1,0)+IF(tp113 = " . $tp['113'] . ",1,0)+IF(tp114 = " . $tp['114'] . ",1,0)+IF(tp115 = " . $tp['115'] . ",1,0)+IF(tp116 = " . $tp['116'] . ",1,0)+IF(tp117 = " . $tp['117'] . ",1,0)+IF(tp118 = " . $tp['118'] . ",1,0)+IF(tp119 = " . $tp['119'] . ",1,0)+IF(tp120 = " . $tp['120'] . ",1,0)+IF(tp121 = " . $tp['121'] . ",1,0)+IF(tp122 = " . $tp['122'] . ",1,0)+IF(tp123 = " . $tp['123'] . ",1,0)+IF(tp124 = " . $tp['124'] . ",1,0)+IF(tp125 = " . $tp['125'] . ",1,0)+IF(tp126 = " . $tp['126'] . ",1,0)+IF(tp127 = " . $tp['127'] . ",1,0)+IF(tp128 = " . $tp['128'] . ",1,0)+IF(tp129 = " . $tp['129'] . ",1,0)+IF(tp130 = " . $tp['130'] . ",1,0)+IF(tp131 = " . $tp['131'] . ",1,0)+IF(tp132 = " . $tp['132'] . ",1,0)+IF(tp133 = " . $tp['133'] . ",1,0)+IF(tp134 = " . $tp['134'] . ",1,0)+IF(tp135 = " . $tp['135'] . ",1,0)+IF(tp136 = " . $tp['136'] . ",1,0)+IF(tp137 = " . $tp['137'] . ",1,0)+IF(tp138 = " . $tp['138'] . ",1,0)+IF(tp139 = " . $tp['139'] . ",1,0)+IF(tp140 = " . $tp['140'] . ",1,0)+IF(tp141 = " . $tp['141'] . ",1,0)+IF(tp142 = " . $tp['142'] . ",1,0)+IF(tp143 = " . $tp['143'] . ",1,0)+IF(tp144 = " . $tp['144'] . ",1,0)+IF(tp145 = " . $tp['145'] . ",1,0)+IF(tp146 = " . $tp['146'] . ",1,0)+IF(tp147 = " . $tp['147'] . ",1,0)+IF(tp148 = " . $tp['148'] . ",1,0)+IF(tp149 = " . $tp['149'] . ",1,0)+IF(tp150 = " . $tp['150'] . ",1,0)+IF(tp151 = " . $tp['151'] . ",1,0)+IF(tp152 = " . $tp['152'] . ",1,0)+IF(tp153 = " . $tp['153'] . ",1,0)+IF(tp154 = " . $tp['154'] . ",1,0)+IF(tp155 = " . $tp['155'] . ",1,0)+IF(tp156 = " . $tp['156'] . ",1,0)+IF(tp157 = " . $tp['157'] . ",1,0)+IF(tp158 = " . $tp['158'] . ",1,0)+IF(tp159 = " . $tp['159'] . ",1,0)+IF(tp160 = " . $tp['160'] . ",1,0)+IF(tp161 = " . $tp['161'] . ",1,0)+IF(tp162 = " . $tp['162'] . ",1,0)+IF(tp163 = " . $tp['163'] . ",1,0)+IF(tp164 = " . $tp['164'] . ",1,0)+IF(tp165 = " . $tp['165'] . ",1,0)+IF(tp166 = " . $tp['166'] . ",1,0)+IF(tp167 = " . $tp['167'] . ",1,0)+IF(tp168 = " . $tp['168'] . ",1,0)+IF(tp169 = " . $tp['169'] . ",1,0)+IF(tp170 = " . $tp['170'] . ",1,0)+IF(tp171 = " . $tp['171'] . ",1,0)+IF(tp172 = " . $tp['172'] . ",1,0)+IF(tp173 = " . $tp['173'] . ",1,0)+IF(tp174 = " . $tp['174'] . ",1,0)+IF(tp175 = " . $tp['175'] . ",1,0)+IF(tp176 = " . $tp['176'] . ",1,0)+IF(tp177 = " . $tp['177'] . ",1,0)+IF(tp178 = " . $tp['178'] . ",1,0)+IF(tp179 = " . $tp['179'] . ",1,0)+IF(tp180 = " . $tp['180'] . ",1,0)+IF(tp181 = " . $tp['181'] . ",1,0)+IF(tp182 = " . $tp['182'] . ",1,0)+IF(tp183 = " . $tp['183'] . ",1,0)+IF(tp184 = " . $tp['184'] . ",1,0)+IF(tp185 = " . $tp['185'] . ",1,0)+IF(tp186 = " . $tp['186'] . ",1,0)+IF(tp187 = " . $tp['187'] . ",1,0)+IF(tp188 = " . $tp['188'] . ",1,0)+IF(tp189 = " . $tp['189'] . ",1,0)+IF(tp190 = " . $tp['190'] . ",1,0)+IF(tp191 = " . $tp['191'] . ",1,0)+IF(tp192 = " . $tp['192'] . ",1,0)+IF(tp193 = " . $tp['193'] . ",1,0)+IF(tp194 = " . $tp['194'] . ",1,0)+IF(tp195 = " . $tp['195'] . ",1,0)+IF(tp196 = " . $tp['196'] . ",1,0)+IF(tp197 = " . $tp['197'] . ",1,0)+IF(tp198 = " . $tp['198'] . ",1,0)+IF(tp199 = " . $tp['199'] . ",1,0)+IF(tp200 = " . $tp['200'] . ",1,0)+IF(tp201 = " . $tp['201'] . ",1,0)+IF(tp202 = " . $tp['202'] . ",1,0)+IF(tp203 = " . $tp['203'] . ",1,0)+IF(tp204 = " . $tp['204'] . ",1,0)+IF(tp205 = " . $tp['205'] . ",1,0)+IF(tp206 = " . $tp['206'] . ",1,0)+IF(tp207 = " . $tp['207'] . ",1,0)+IF(tp208 = " . $tp['208'] . ",1,0)+IF(tp209 = " . $tp['209'] . ",1,0)+IF(tp210 = " . $tp['210'] . ",1,0)+IF(tp211 = " . $tp['211'] . ",1,0)+IF(tp212 = " . $tp['212'] . ",1,0)+IF(tp213 = " . $tp['213'] . ",1,0)+IF(tp214 = " . $tp['214'] . ",1,0)+IF(tp215 = " . $tp['215'] . ",1,0)+IF(tp216 = " . $tp['216'] . ",1,0)+IF(tp217 = " . $tp['217'] . ",1,0)+IF(tp218 = " . $tp['218'] . ",1,0)+IF(tp219 = " . $tp['219'] . ",1,0)+IF(tp220 = " . $tp['220'] . ",1,0)+IF(tp221 = " . $tp['221'] . ",1,0)+IF(tp222 = " . $tp['222'] . ",1,0)+IF(tp223 = " . $tp['223'] . ",1,0)+IF(tp224 = " . $tp['224'] . ",1,0)+IF(tp225 = " . $tp['225'] . ",1,0)+IF(tp226 = " . $tp['226'] . ",1,0)+IF(tp227 = " . $tp['227'] . ",1,0)+IF(tp228 = " . $tp['228'] . ",1,0)+IF(tp229 = " . $tp['229'] . ",1,0)+IF(tp230 = " . $tp['230'] . ",1,0)+IF(tp231 = " . $tp['231'] . ",1,0)+IF(tp232 = " . $tp['232'] . ",1,0)+IF(tp233 = " . $tp['233'] . ",1,0)+IF(tp234 = " . $tp['234'] . ",1,0)+IF(tp235 = " . $tp['235'] . ",1,0)+IF(tp236 = " . $tp['236'] . ",1,0)+IF(tp237 = " . $tp['237'] . ",1,0)+IF(tp238 = " . $tp['238'] . ",1,0)+IF(tp239 = " . $tp['239'] . ",1,0)+IF(tp240 = " . $tp['240'] . ",1,0)+IF(tp241 = " . $tp['241'] . ",1,0)+IF(tp242 = " . $tp['242'] . ",1,0)+IF(tp243 = " . $tp['243'] . ",1,0)+IF(tp244 = " . $tp['244'] . ",1,0)+IF(tp245 = " . $tp['245'] . ",1,0)+IF(tp246 = " . $tp['246'] . ",1,0)+IF(tp247 = " . $tp['247'] . ",1,0)+IF(tp248 = " . $tp['248'] . ",1,0)+IF(tp249 = " . $tp['249'] . ",1,0)+IF(tp250 = " . $tp['250'] . ",1,0)+IF(tp251 = " . $tp['251'] . ",1,0)+IF(tp252 = " . $tp['252'] . ",1,0)+IF(tp253 = " . $tp['253'] . ",1,0)+IF(tp254 = " . $tp['254'] . ",1,0)+IF(tp255 = " . $tp['255'] . ",1,0)+IF(tp256 = " . $tp['256'] . ",1,0)+IF(tp257 = " . $tp['257'] . ",1,0)+IF(tp258 = " . $tp['258'] . ",1,0)+IF(tp259 = " . $tp['259'] . ",1,0)+IF(tp260 = " . $tp['260'] . ",1,0)+IF(tp261 = " . $tp['261'] . ",1,0)+IF(tp262 = " . $tp['262'] . ",1,0)+IF(tp263 = " . $tp['263'] . ",1,0)+IF(tp264 = " . $tp['264'] . ",1,0)+IF(tp265 = " . $tp['265'] . ",1,0)+IF(tp266 = " . $tp['266'] . ",1,0)+IF(tp267 = " . $tp['267'] . ",1,0)+IF(tp268 = " . $tp['268'] . ",1,0)+IF(tp269 = " . $tp['269'] . ",1,0)+IF(tp270 = " . $tp['270'] . ",1,0)+IF(tp271 = " . $tp['271'] . ",1,0)+IF(tp272 = " . $tp['272'] . ",1,0)+IF(tp273 = " . $tp['273'] . ",1,0)+IF(tp274 = " . $tp['274'] . ",1,0)+IF(tp275 = " . $tp['275'] . ",1,0)+IF(tp276 = " . $tp['276'] . ",1,0)+IF(tp277 = " . $tp['277'] . ",1,0)+IF(tp278 = " . $tp['278'] . ",1,0)+IF(tp279 = " . $tp['279'] . ",1,0)+IF(tp280 = " . $tp['280'] . ",1,0)+IF(tp281 = " . $tp['281'] . ",1,0)+IF(tp282 = " . $tp['282'] . ",1,0)+IF(tp283 = " . $tp['283'] . ",1,0)+IF(tp284 = " . $tp['284'] . ",1,0)+IF(tp285 = " . $tp['285'] . ",1,0)+IF(tp286 = " . $tp['286'] . ",1,0)+IF(tp287 = " . $tp['287'] . ",1,0)+IF(tp288 = " . $tp['288'] . ",1,0)+IF(tp289 = " . $tp['289'] . ",1,0)+IF(tp290 = " . $tp['290'] . ",1,0)+IF(tp291 = " . $tp['291'] . ",1,0)+IF(tp292 = " . $tp['292'] . ",1,0)+IF(tp293 = " . $tp['293'] . ",1,0)+IF(tp294 = " . $tp['294'] . ",1,0)+IF(tp295 = " . $tp['295'] . ",1,0)+IF(tp296 = " . $tp['296'] . ",1,0)+IF(tp297 = " . $tp['297'] . ",1,0)+IF(tp298 = " . $tp['298'] . ",1,0)+IF(tp299 = " . $tp['299'] . ",1,0)+IF(tp300 = " . $tp['300'] . ",1,0)+IF(tp301 = " . $tp['301'] . ",1,0)+IF(tp302 = " . $tp['302'] . ",1,0)+IF(tp303 = " . $tp['303'] . ",1,0)+IF(tp304 = " . $tp['304'] . ",1,0)+IF(tp305 = " . $tp['305'] . ",1,0)+IF(tp306 = " . $tp['306'] . ",1,0)+IF(tp307 = " . $tp['307'] . ",1,0)+IF(tp308 = " . $tp['308'] . ",1,0)+IF(tp309 = " . $tp['309'] . ",1,0)+IF(tp310 = " . $tp['310'] . ",1,0)+IF(tp311 = " . $tp['311'] . ",1,0)+IF(tp312 = " . $tp['312'] . ",1,0)+IF(tp313 = " . $tp['313'] . ",1,0)+IF(tp314 = " . $tp['314'] . ",1,0)+IF(tp315 = " . $tp['315'] . ",1,0)+IF(tp316 = " . $tp['316'] . ",1,0)+IF(tp317 = " . $tp['317'] . ",1,0)+IF(tp318 = " . $tp['318'] . ",1,0)+IF(tp319 = " . $tp['319'] . ",1,0)+IF(tp320 = " . $tp['320'] . ",1,0)+IF(tp321 = " . $tp['321'] . ",1,0)+IF(tp322 = " . $tp['322'] . ",1,0)+IF(tp323 = " . $tp['323'] . ",1,0)+IF(tp324 = " . $tp['324'] . ",1,0)+IF(tp325 = " . $tp['325'] . ",1,0)+IF(tp326 = " . $tp['326'] . ",1,0)+IF(tp327 = " . $tp['327'] . ",1,0)+IF(tp328 = " . $tp['328'] . ",1,0)+IF(tp329 = " . $tp['329'] . ",1,0)+IF(tp330 = " . $tp['330'] . ",1,0)+IF(tp331 = " . $tp['331'] . ",1,0)+IF(tp332 = " . $tp['332'] . ",1,0)+IF(tp333 = " . $tp['333'] . ",1,0)+IF(tp334 = " . $tp['334'] . ",1,0)+IF(tp335 = " . $tp['335'] . ",1,0)+IF(tp336 = " . $tp['336'] . ",1,0)+IF(tp337 = " . $tp['337'] . ",1,0)+IF(tp338 = " . $tp['338'] . ",1,0)+IF(tp339 = " . $tp['339'] . ",1,0)+IF(tp340 = " . $tp['340'] . ",1,0)+IF(tp341 = " . $tp['341'] . ",1,0)+IF(tp342 = " . $tp['342'] . ",1,0)+IF(tp343 = " . $tp['343'] . ",1,0)+IF(tp344 = " . $tp['344'] . ",1,0)+IF(tp345 = " . $tp['345'] . ",1,0)+IF(tp346 = " . $tp['346'] . ",1,0)+IF(tp347 = " . $tp['347'] . ",1,0)+IF(tp348 = " . $tp['348'] . ",1,0)+IF(tp349 = " . $tp['349'] . ",1,0)+IF(tp350 = " . $tp['350'] . ",1,0)+IF(tp351 = " . $tp['351'] . ",1,0)+IF(tp352 = " . $tp['352'] . ",1,0)+IF(tp353 = " . $tp['353'] . ",1,0)+IF(tp354 = " . $tp['354'] . ",1,0)+IF(tp355 = " . $tp['355'] . ",1,0)+IF(tp356 = " . $tp['356'] . ",1,0)+IF(tp357 = " . $tp['357'] . ",1,0)+IF(tp358 = " . $tp['358'] . ",1,0)+IF(tp359 = " . $tp['359'] . ",1,0)+IF(tp360 = " . $tp['360'] . ",1,0)+IF(tp361 = " . $tp['361'] . ",1,0)+IF(tp362 = " . $tp['362'] . ",1,0)+IF(tp363 = " . $tp['363'] . ",1,0)+IF(tp364 = " . $tp['364'] . ",1,0)+IF(tp365 = " . $tp['365'] . ",1,0)+IF(tp366 = " . $tp['366'] . ",1,0)+IF(tp367 = " . $tp['367'] . ",1,0)+IF(tp368 = " . $tp['368'] . ",1,0)+IF(tp369 = " . $tp['369'] . ",1,0)+IF(tp370 = " . $tp['370'] . ",1,0)+IF(tp371 = " . $tp['371'] . ",1,0)+IF(tp372 = " . $tp['372'] . ",1,0)+IF(tp373 = " . $tp['373'] . ",1,0)+IF(tp374 = " . $tp['374'] . ",1,0)+IF(tp375 = " . $tp['375'] . ",1,0)+IF(tp376 = " . $tp['376'] . ",1,0)+IF(tp377 = " . $tp['377'] . ",1,0)+IF(tp378 = " . $tp['378'] . ",1,0)+IF(tp379 = " . $tp['379'] . ",1,0)+IF(tp380 = " . $tp['380'] . ",1,0)+IF(tp381 = " . $tp['381'] . ",1,0)+IF(tp382 = " . $tp['382'] . ",1,0)+IF(tp383 = " . $tp['383'] . ",1,0)+IF(tp384 = " . $tp['384'] . ",1,0)+IF(tp385 = " . $tp['385'] . ",1,0)+IF(tp386 = " . $tp['386'] . ",1,0)+IF(tp387 = " . $tp['387'] . ",1,0)+IF(tp388 = " . $tp['388'] . ",1,0)+IF(tp389 = " . $tp['389'] . ",1,0)+IF(tp390 = " . $tp['390'] . ",1,0)+IF(tp391 = " . $tp['391'] . ",1,0)+IF(tp392 = " . $tp['392'] . ",1,0)+IF(tp393 = " . $tp['393'] . ",1,0)+IF(tp394 = " . $tp['394'] . ",1,0)+IF(tp395 = " . $tp['395'] . ",1,0)+IF(tp396 = " . $tp['396'] . ",1,0)+IF(tp397 = " . $tp['397'] . ",1,0)+IF(tp398 = " . $tp['398'] . ",1,0)+IF(tp399 = " . $tp['399'] . ",1,0))
DESC;";

	$result = mysql_query($sql);
	$s = mysql_fetch_assoc($result);	
	$this->imageString .= $s['chr'];
	} 
		

}