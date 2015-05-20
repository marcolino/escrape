<?php

  $sourcesDefinitions = [
    "toe" => [
      "patterns-to-remove-from-body-before-sum" => [
        "/<span.*?>Visite:<\/span>\s*<span.*?>\s*\d+<\/span>/s",
        "/<span.*?>Impression:<\/span>\s*<span.*?>\s+[\d.]+<\/span>/s",
        "/<i class=\"icon-clock\"><\/i>\s*\d+\/\d+\/\d+\s*\d+:\d+:\d+/s",
      ],
    ],
  ];

  $data_1 = file_get_contents("pam1body.html");
  $data_2 = file_get_contents("pam2body.html");

  foreach ($sourcesDefinitions as $sourceKey => $source) {
    print "sourcekey: [$sourceKey]\n";
    $patternsToRemove = $source["patterns-to-remove-from-body-before-sum"];
    foreach ($patternsToRemove as $patternToRemove) {
      print "removing pattern [$patternToRemove]\n";
      $data = preg_replace($patternToRemove, "", $data_1);
      if ($data === $data_1) { print "NO changes in data 1!\n"; } else { print "changes in data 1\n"; }
      $data_1 = $data;
      $data = preg_replace($patternToRemove, "", $data_2);
      if ($data === $data_2) { print "NO changes in data 2!\n"; } else { print "changes in data 2\n"; }
      $data_2 = $data;
    }
    print "/sourcekey\n";
  }

  $md5_1 = md5($data_1);
  $md5_2 = md5($data_2);

  if ($md5_1 === $md5_2) {
  	print "files contents are the same.\n";
  } else {
  	print "files contents differ...\n";
    file_put_contents("pam1bodyafterchangesremoved.html", $data_1);
    file_put_contents("pam2bodyafterchangesremoved.html", $data_2);
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
  "toe" => [
    "patterns-to-remove-from-body-before-sum" => [
      "/<span.*?>Visite:<\/span>\s*<span.*?>\s*\d+<\/span>/s",
      "/<span-*?>Impression:<\/span>\s*<span\*?>\s*[\d\.]+<\/span>/s",
      "/<i class=\"icon-clock\"><\/i>\s*\d+\/\d+\/\d+\s*\d+:\d+:\d+/s",
    ],
  ],

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

/*
<span.*?>Visite:<\/span>\s*<span.*?>\s*\d+<\/span>
431c431
< </td></tr><tr><td class="line-number" value="429"></td><td class="line-content">                                        <span class="html-tag">&lt;span <span class="html-attribute-name">style</span>="<span class="html-attribute-value">display:inline-block; min-width:90px;</span>"&gt;</span>Visite:<span class="html-tag">&lt;/span&gt;</span> <span class="html-tag">&lt;span <span class="html-attribute-name">class</span>="<span class="html-attribute-value">pi-weight-600 pi-text-bg-dark lead-18</span>"&gt;</span>2567<span class="html-tag">&lt;/span&gt;</span><span class="html-tag">&lt;/li&gt;</span>
---
> </td></tr><tr><td class="line-number" value="429"></td><td class="line-content">                                        <span class="html-tag">&lt;span <span class="html-attribute-name">style</span>="<span class="html-attribute-value">display:inline-block; min-width:90px;</span>"&gt;</span>Visite:<span class="html-tag">&lt;/span&gt;</span> <span class="html-tag">&lt;span <span class="html-attribute-name">class</span>="<span class="html-attribute-value">pi-weight-600 pi-text-bg-dark lead-18</span>"&gt;</span>2568<span class="html-tag">&lt;/span&gt;</span><span class="html-tag">&lt;/li&gt;</span>

<span-*?>Impression:<\/span>\s*<span\*?>\s*[\d\.]+<\/span>
434c434
< </td></tr><tr><td class="line-number" value="432"></td><td class="line-content">                    89.631<span class="html-tag">&lt;/span&gt;</span><span class="html-tag">&lt;/li&gt;</span>
---
> </td></tr><tr><td class="line-number" value="432"></td><td class="line-content">                    89.633<span class="html-tag">&lt;/span&gt;</span><span class="html-tag">&lt;/li&gt;</span>

<i class=\"icon-clock\"><\/i>\s*19/05/2015\s*20:24:48
803c803
< </td></tr><tr><td class="line-number" value="801"></td><td class="line-content">                                <span class="html-tag">&lt;p <span class="html-attribute-name">class</span>="<span class="html-attribute-value">pi-no-margin-bottom pi-small-text</span>"&gt;</span><span class="html-tag">&lt;i <span class="html-attribute-name">class</span>="<span class="html-attribute-value">icon-clock</span>"&gt;</span><span class="html-tag">&lt;/i&gt;</span> 19/05/2015 20:24:33 <span class="html-tag">&lt;a <span class="html-attribute-name">href</span>="<a class="html-attribute-value html-external-link" target="_blank" href="http://www.torinoerotica.com/annuncio?id=3900#">#</a>"&gt;</span><span class="html-tag">&lt;/a&gt;</span><span class="html-tag">&lt;/p&gt;</span>
---
> </td></tr><tr><td class="line-number" value="801"></td><td class="line-content">                                <span class="html-tag">&lt;p <span class="html-attribute-name">class</span>="<span class="html-attribute-value">pi-no-margin-bottom pi-small-text</span>"&gt;</span><span class="html-tag">&lt;i <span class="html-attribute-name">class</span>="<span class="html-attribute-value">icon-clock</span>"&gt;</span><span class="html-tag">&lt;/i&gt;</span> 19/05/2015 20:24:48 <span class="html-tag">&lt;a <span class="html-attribute-name">href</span>="<a class="html-attribute-value html-external-link" target="_blank" href="http://www.torinoerotica.com/annuncio?id=3900#">#</a>"&gt;</span><span class="html-tag">&lt;/a&gt;</span><span class="html-tag">&lt;/p&gt;</span>
*/

?>