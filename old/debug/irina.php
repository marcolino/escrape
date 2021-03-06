<?php

  $sourcesDefinitions = [
    "facebook" => [
      "patterns-to-remove-from-body-before-sum" => [
        "/<img src=\".*?\/testalino_homepage\d+.jpg\"/s",
        "/Visite:\s+<\/td><td><div>\s+\d+\s+<\/div><\/td>/s",
        "/\?t=\d+/s",
        "/email-protection#.+\"/s",
      ],
    ],
  ];

  $data_1 = file_get_contents("irina1body.html");
  $data_2 = file_get_contents("irina2body.html");

  foreach ($sourcesDefinitions as $sourceKey => $source) {
    $patternsToRemove = $source["patterns-to-remove-from-body-before-sum"];
    foreach ($patternsToRemove as $patternToRemove) {
      print "removing pattern [$patternToRemove]\n";
      $data = preg_replace($patternToRemove, "", $data_1);
      if ($data === $data_1) { print "NOT changed in data 1!\n"; }
      $data_1 = $data;
      $data = preg_replace($patternToRemove, "", $data_2);
      if ($data === $data_2) { print "NOT changed in data 2!\n"; }
      $data_2 = $data;
    }
  }

  $md5_1 = md5($data_1);
  $md5_2 = md5($data_2);

  if ($md5_1 === $md5_2) {
  	print "files contents are the same.\n";
  } else {
  	print "files contents differ...\n";
  }

/*

  ...
  "sgi" => [
    "patterns-to-remove-from-body-before-sum" => [
      "/<img src=\".*?\/testalino_homepage\d+.jpg\"/s",
      "/Visite:\s+<\/td><td><div>\s+\d+\s+<\/div><\/td>/s",
      "/\?t=\d+/s",
      "/email-protection#.+\"/s",
    ],
  ],
  ...

  $patternsToRemove = $source["patterns-to-remove-from-body-before-sum"];
  foreach ($patternsToRemove as $patternToRemove) { // remove all patterns to remove from body before sum
    $contents = preg_replace($patternToRemove, "", $contents);
  }

*/


/*
"img src=".*?\/testalino_homepage\d+.jpg
Visite: \s+</td><td><div>\s+\d+\s+</div></td>
?t=\d+>
email-protection#\d+"

110c110
<                     <img src="http://www.sexyguidaitalia.com/images/escort_trans/testalino_homepage6.jpg" id="ctl00_logo" border="0" alt="logo Sexy Guida Italia" title="Annunci escort Trans, Coppie, Donne, Uomini" />
---
>                     <img src="http://www.sexyguidaitalia.com/images/escort_trans/testalino_homepage18.jpg" id="ctl00_logo" border="0" alt="logo Sexy Guida Italia" title="Annunci escort Trans, Coppie, Donne, Uomini" />
410c410
< 			110272
---
> 			110273
434c434
< 		<td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/copertina.jpg?t=635675495800092058><img title="IRINA - Accompagnatrice Torino - Foto: copertina.jpg" class="fotoClick" src="../../public/25275/anteprime/copertina.jpg?t=635675495800092058" alt="IRINA - Accompagnatrice Torino - Foto: copertina.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/2.jpg?t=635675495800092058><img title="IRINA - Accompagnatrice Torino - Foto: 2.jpg" class="fotoClick" src="../../public/25275/anteprime/2.jpg?t=635675495800092058" alt="IRINA - Accompagnatrice Torino - Foto: 2.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/3.jpg?t=635675495800092058><img title="IRINA - Accompagnatrice Torino - Foto: 3.jpg" class="fotoClick" src="../../public/25275/anteprime/3.jpg?t=635675495800092058" alt="IRINA - Accompagnatrice Torino - Foto: 3.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/4.jpg?t=635675495800092058><img title="IRINA - Accompagnatrice Torino - Foto: 4.jpg" class="fotoClick" src="../../public/25275/anteprime/4.jpg?t=635675495800092058" alt="IRINA - Accompagnatrice Torino - Foto: 4.jpg" style="border-width:0px;" /></a></td>
---
> 		<td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/copertina.jpg?t=635675496514261312><img title="IRINA - Accompagnatrice Torino - Foto: copertina.jpg" class="fotoClick" src="../../public/25275/anteprime/copertina.jpg?t=635675496514261312" alt="IRINA - Accompagnatrice Torino - Foto: copertina.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/2.jpg?t=635675496514261312><img title="IRINA - Accompagnatrice Torino - Foto: 2.jpg" class="fotoClick" src="../../public/25275/anteprime/2.jpg?t=635675496514261312" alt="IRINA - Accompagnatrice Torino - Foto: 2.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/3.jpg?t=635675496514261312><img title="IRINA - Accompagnatrice Torino - Foto: 3.jpg" class="fotoClick" src="../../public/25275/anteprime/3.jpg?t=635675496514261312" alt="IRINA - Accompagnatrice Torino - Foto: 3.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/4.jpg?t=635675496514261312><img title="IRINA - Accompagnatrice Torino - Foto: 4.jpg" class="fotoClick" src="../../public/25275/anteprime/4.jpg?t=635675496514261312" alt="IRINA - Accompagnatrice Torino - Foto: 4.jpg" style="border-width:0px;" /></a></td>
436c436
< 		<td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/5.jpg?t=635675495800092058><img title="IRINA - Accompagnatrice Torino - Foto: 5.jpg" class="fotoClick" src="../../public/25275/anteprime/5.jpg?t=635675495800092058" alt="IRINA - Accompagnatrice Torino - Foto: 5.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/6.jpg?t=635675495800092058><img title="IRINA - Accompagnatrice Torino - Foto: 6.jpg" class="fotoClick" src="../../public/25275/anteprime/6.jpg?t=635675495800092058" alt="IRINA - Accompagnatrice Torino - Foto: 6.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/7.jpg?t=635675495800092058><img title="IRINA - Accompagnatrice Torino - Foto: 7.jpg" class="fotoClick" src="../../public/25275/anteprime/7.jpg?t=635675495800092058" alt="IRINA - Accompagnatrice Torino - Foto: 7.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/8.jpg?t=635675495800092058><img title="IRINA - Accompagnatrice Torino - Foto: 8.jpg" class="fotoClick" src="../../public/25275/anteprime/8.jpg?t=635675495800092058" alt="IRINA - Accompagnatrice Torino - Foto: 8.jpg" style="border-width:0px;" /></a></td>
---
> 		<td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/5.jpg?t=635675496514261312><img title="IRINA - Accompagnatrice Torino - Foto: 5.jpg" class="fotoClick" src="../../public/25275/anteprime/5.jpg?t=635675496514261312" alt="IRINA - Accompagnatrice Torino - Foto: 5.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/6.jpg?t=635675496514261312><img title="IRINA - Accompagnatrice Torino - Foto: 6.jpg" class="fotoClick" src="../../public/25275/anteprime/6.jpg?t=635675496514261312" alt="IRINA - Accompagnatrice Torino - Foto: 6.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/7.jpg?t=635675496514261312><img title="IRINA - Accompagnatrice Torino - Foto: 7.jpg" class="fotoClick" src="../../public/25275/anteprime/7.jpg?t=635675496514261312" alt="IRINA - Accompagnatrice Torino - Foto: 7.jpg" style="border-width:0px;" /></a></td><td class="voiceThumbs" style="background-color:#FFFF00;"><a rel='group' class='fancybox' href=../../public/25275/8.jpg?t=635675496514261312><img title="IRINA - Accompagnatrice Torino - Foto: 8.jpg" class="fotoClick" src="../../public/25275/anteprime/8.jpg?t=635675496514261312" alt="IRINA - Accompagnatrice Torino - Foto: 8.jpg" style="border-width:0px;" /></a></td>
779c779
< Sexy Guida Italia – Inserzioni per adulti e annunci erotici – Info +39.393.4748467 oppure +39.393.9358631 – <a href="/cdn-cgi/l/email-protection#9ff6f1f9f0dfecfae7e6f8eaf6fbfef6ebfef3f6feb1fcf0f2"><span class="__cf_email__" data-cfemail="51383f373e112234292836243835303825303d38307f323e3c">[email&#160;protected]</span><script cf-hash='f9e31' type="text/javascript">
---
> Sexy Guida Italia – Inserzioni per adulti e annunci erotici – Info +39.393.4748467 oppure +39.393.9358631 – <a href="/cdn-cgi/l/email-protection#721b1c141d3201170a0b15071b16131b06131e1b135c111d1f"><span class="__cf_email__" data-cfemail="452c2b232a0536203d3c22302c21242c3124292c246b262a28">[email&#160;protected]</span><script cf-hash='f9e31' type="text/javascript">
*/

?>