<?php
// OO modifications by Mircea Vutcovici
// based on the script maded by Colin Viebrock from:
// http://viebrock.ca/code/10/turing-protection

if (!extension_loaded('gd')) {
    if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN')) {
        dl('php_gd.dll');
    } else {
        dl('gd.so');
    }
}

class Turing{
    var $src = 'abcdefghjkmnprstuvwxyz23456789';
    var $length = 8;
    var $fontFileName = 'bboron.ttf';
    var $min_font_size = 12;
    var $max_font_size = 20;
    var $min_angle = -15;
    var $max_angle = 15;
    var $char_padding = 2;
    var $key = '';
    var $x_padding = 12;
    var $output_type='png';

    function Turing(){ //{{{
        if ( mt_rand( 0, 1 ) == 0 ) {
            $this->src = strtoupper( $this->src );
        }
    } //}}}

    function setLength( $length=8 ){ //{{{
        $this->length = $length;
        $this->key='';
        $this->generateKey();
    }//}}}

    function setFontFile( $fontFileName ){ //{{{
        if ( file_exists( $fontFileName ) ){
            $this->fontFileName = $fontFileName;
            return TRUE;
        }
        return FALSE;
    } //}}}

    function setOutputType( $output_type ){ //{{{
        switch( $output_type){
            case 'jpeg':
            case 'jpg':
                $this->output_type='jpeg';
                break;
            case 'png':
                $this->output_type='png';
                break;
            default:
                return FALSE;
        }
        return TRUE;
    } //}}}

    function generateKey(){ //{{{
        $this->key='';
        $srclen = strlen( $this->src )-1;
        for($i=0; $i<$this->length; $i++) {
            $char = substr($this->src, mt_rand(0,$srclen), 1);
            $this->key .= $char;
        }
    } //}}}
    
    function getKey(){ //{{{
        return $this->key;
    } //}}}

    function setKey($key){ //{{{
        if( isset($key) && is_string($key) ){
            $this->length=strlen($key);
            $this->key=$key;
            return TRUE;
        }
        return FALSE;
    } //}}}

    function getAllowedChars(){ //{{{
        return $this->src;
    } //}}}

    function setAllowedChars($chars){ //{{{
        $this->src=$chars;
        $this->generateKey();
    } //}}}

    function isRightKey($key){ //{{{
        if ( strtolower($key) === strtolower($this->key) ){
            return TRUE;
        }
        return FALSE;
    } //}}}

    function displayImage(){ //{{{
        $data=array();
        $image_width = $image_height = 0;
        for($i=0; $i<$this->length; $i++) {
            //$char = substr($src, mt_rand(0,$srclen), 1);
            //$this->key .= $char;
            $char = $this->key[$i];
            $size = mt_rand($this->min_font_size, $this->max_font_size);
            $angle = mt_rand($this->min_angle, $this->max_angle);

            $bbox = ImageTTFBBox( $size, $angle, $this->fontFileName, $char );

            $char_width = max($bbox[2],$bbox[4]) - min($bbox[0],$bbox[6]);
            $char_height = max($bbox[1],$bbox[3]) - min($bbox[7],$bbox[5]);

            $image_width += $char_width + $this->char_padding;
            $image_height = max($image_height, $char_height);

            $data[] = array(
                'char'        => $char,
                'size'        => $size,
                'angle'        => $angle,
                'height'    => $char_height,
                'width'        => $char_width,
            );

        }

        $image_width += ($this->x_padding * 2);
        $image_height = ($image_height * 1.5) + 2;
                                                                                                                                     
        $im = ImageCreate($image_width, $image_height);
        $r = 51 * mt_rand(4,5);
        $g = 51 * mt_rand(4,5);
        $b = 51 * mt_rand(4,5);
        $color_bg        = ImageColorAllocate($im,  $r,  $g,  $b );
        
        $r = 51 * mt_rand(3,4);
        $g = 51 * mt_rand(3,4);
        $b = 51 * mt_rand(3,4);
        $color_line0    = ImageColorAllocate($im,  $r,  $g,  $b );

        $r = 51 * mt_rand(3,4);
        $g = 51 * mt_rand(3,4);
        $b = 51 * mt_rand(3,4);
        $color_line1    = ImageColorAllocate($im,  $r,  $g,  $b );

        $r = 51 * mt_rand(1,2);
        $g = 51 * mt_rand(1,2);
        $b = 51 * mt_rand(1,2);
        $color_text        = ImageColorAllocate($im,  $r,  $g,  $b );

        $color_border    = ImageColorAllocate($im,   0,   0,   0 );

        // make the random background lines

        for($l=0; $l<10; $l++) {

            $c = 'color_line' . ($l%2);

            $lx = mt_rand(0,$image_width+$image_height);
            $lw = mt_rand(0,3);
            if ($lx > $image_width) {
                $lx -= $image_width;
                ImageFilledRectangle($im, 0, $lx, $image_width-1, $lx+$lw, $$c );
            } else {
                ImageFilledRectangle($im, $lx, 0, $lx+$lw, $image_height-1, $$c );
            }
        }

        // output each character

        $pos_x = $this->x_padding + ($this->char_padding / 2);
        foreach($data as $d) {

            $pos_y = ( ( $image_height + $d['height'] ) / 2 );
            ImageTTFText($im, $d['size'], $d['angle'], $pos_x, $pos_y, $color_text, $this->fontFileName, $d['char'] );

            $pos_x += $d['width'] + $this->char_padding;
        }

        // a nice border
        ImageRectangle($im, 0, 0, $image_width-1, $image_height-1, $color_border);

        // display it
        switch ($this->output_type) {
          case 'jpeg':
            Header('Content-type: image/jpeg');
            ImageJPEG($im,NULL,100);
            break;
          case 'png':
          default:
            Header('Content-type: image/png');
            ImagePNG($im);
            break;
        }
        ImageDestroy($im);

    }//}}} end function displayImage

}
// vim: set noet cindent ts=2 sw=2:
?>
