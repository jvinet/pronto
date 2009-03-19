<?PHP

/* Poidsy 0.4 - http://chris.smith.name/projects/poidsy
 * Copyright (c) 2008 Chris Smith
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

 define('SREG_NICKNAME', 'openid.sreg.nickname');
 define('SREG_EMAIL', 'openid.sreg.email');
 define('SREG_FULLNAME', 'openid.sreg.fullname');
 define('SREG_DOB', 'openid.sreg.dob');
 define('SREG_GENDER', 'openid.sreg.gender');
 define('SREG_POSTCODE', 'openid.sreg.postcode');
 define('SREG_COUNTRY', 'openid.sreg.country');
 define('SREG_LANGUAGE', 'openid.sreg.language');
 define('SREG_TIMEZONE', 'openid.sreg.timezone');

 define('SREG_ALL', SREG_NICKNAME . ',' . SREG_EMAIL . ',' . SREG_FULLNAME
             . ',' . SREG_DOB . ',' . SREG_GENDER . ',' . SREG_POSTCODE . ','
             . SREG_COUNTRY . ',' . SREG_LANGUAGE . ', ' . SREG_TIMEZONE);

 function parseSRegResponse() {
  foreach (explode(',', SREG_ALL) as $reg) {
   $reg = str_replace('.', '_', $reg);
   if (isset($_REQUEST[$reg])) {
    $_SESSION['openid']['sreg'][substr($reg, 12)] = $_REQUEST[$reg];
   }
  }
 }

?>
