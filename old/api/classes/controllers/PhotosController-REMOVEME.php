<?php
/**
 * Photos controller
 * 
 * @package PhotosController
 * @author  Marco Solari <marcosolari@gmail.com>
 */

class PhotosController {

  public function __construct($router) {
    $this->router = $router;
    $this->db = $router->db;
  }

  /**
  * Add a photo
  *
  * @param  array: photo object
  * @return integer: -1   if duplicated photo (not added)
  *                  >= 0 id of added photo
  */
  public function add($photo) {
    $photo = new Image(); # TODO: $photo ??? override the parameter ???

    # get all photos for the person id of the photo
    $photos = $this->getPhotosByPerson($photo["id_person"]);

    # check for photo duplication
    $sum = md5($bitmap);
    $photo["sum"] = $sum;
    if ($this->checkPhotoDuplication($photos, $sum)) {
      $this->router->log("info", "photo " . $photo["url"] . " for person id " . $photo["id_person"] . " is a duplicate");
      return -1; // duplicate found
    }

    # check for photo similarity
    $signature = $imagesTool->getSignaturefromBitmap($bitmap, $photo["url"]);
    $photo["signature"] = $signature;
    if ($imagesTool->checkImageSimilarity($signature, $photos)) {
      $this->router->log("info", "photo " . $photo["url"] . " for person id " . $photo["id_person"] . " has a similar photo");
      return -1; // duplicate found
    }

/*
    # check for photo truthfulness
    if ($imagesTool->checkImageThruthfulness($photo["url"])) {
      $photo["thruthfulness"] = true;
    } else {
      $photo["thruthfulness"] = false;
      $this->router->log("info", "photo " . $photo["url"] . " for person id " . $photo["id_person"] . " does not seem thrutful");
    }
*/

    $photo["type"] = $this->getTypeFromUrl($photo["url"]);
    $photo["name"] = $photo["sum"]; # TODO: a progressive integer counter?

    # assert photos full path existence
    $pathNameFull = self::DB_PHOTOS_PATH_FULL . $photo["id_person"] . "/";
    if (!file_exists($pathNameFull)) {
      if (!@mkdir($pathNameFull, 0777, true)) { # TODO: let everybody (developer) to write dir: DEBUG ONLY!
        throw new Exception("can't create folder $pathNameFull");
      }
      $this->router->log("debug", "the directory $pathNameFull has been created");
    } else {
      ; # directory already exists, not the first photo full for this person
    }

    # save photo full
    $bitmapFull = $bitmap;
    $photoPathFull = $this->photoToPath($photo, "full");
    if (file_exists($photoPathFull)) {
      $this->router->log("error", "the photo file $photoPathFull exists already, overwriting");
    }
    if (@file_put_contents($photoPathFull, $bitmapFull, LOCK_EX) === FALSE) {
      throw new Exception("can't save photo to file $photoPathFull");
    }

    # assert photos thumbnail path existence
    $pathNameThumbnail = self::DB_PHOTOS_PATH_THUMBNAIL . $photo["id_person"] . "/";
    if (!file_exists($pathNameThumbnail)) {
      if (!@mkdir($pathNameThumbnail, 0777, true)) { # TODO: let everybody (developer) to write dir: DEBUG ONLY!
        throw new Exception("can't create folder $pathNameThumbnail");
      }
      $this->router->log("debug", "the directory $pathNameThumbnail has been created");
    } else {
      ; # directory already exists, not the first photo thumbnail for this person
    }

    # save photo thumbnail
    $bitmapThumbnail = $this->scaleBitmap($bitmap, "thumbnail");
    $photoPathThumbnail = $this->photoToPath($photo, "thumbnail");
    if (file_exists($photoPathThumbnail)) {
      $this->router->log("error", "the photo file $photoPathThumbnail exists already, overwriting");
    }
    if (@file_put_contents($photoPathThumbnail, $bitmapThumbnail, LOCK_EX) === FALSE) {
      throw new Exception("can't save photo to file $photoPathThumbnail");
    }

    return $this->db->add("photo", $photo); # add photo to db
  }
  
  public function get($id) {
    return $this->db->get("photo", "id");
  }

/*
  public function set($id, $photo) {
    # TODO: check if we need to change some properties of image on disk...
    return $this->db->set("photo", $id, $photo);
$this->router->log("debug", "setting photo [$photo] to db for id [$id]");
  }
*/

  public function delete($id) {
    $photo = $this->get($id);
    $pathFull = photoToPath($photo, "full");
    if (file_exists($pathFull)) {
      if (@unlink($pathFull) === false) {
        $this->router->log("warning", "the photo at path $pathFull can't be deleted: " .  error_get_last()["message"]);
      }
    } else {
      $this->router->log("warning", "the photo at path $pathFull can't be deleted, it does not exist");
    }
    return $this->db->delete("photo", $id);
  }

  public function deleteByPerson($idPerson) {
    $photos = $this->getPhotosByPerson($idPerson);
    foreach ($photos as $photo) {
      $this->delete($photo["id"]);
    }
  }

  public function getPhotosByPerson($idPerson) {
    return $this->db->getByField("photo", "id_person", $idPerson);
  }

  public function getPhotoShowcase($idPerson) { # TODO...
    return "http://localhost/escrape/api/db/photos/1-toe-123456/small-001.jpeg";
  }

  private function scaleBitmap($bitmap, $mode) {
    switch ($mode) {
      case "full":
        ; // no need to scale bitmap
        break;
      case "thumbnail":
        $image = new Image();
        $photo = $image->fromArray([ "bitmap" => $bitmap ])->toArray();
        $bitmap = $photo["bitmap"];
        unset($image);
        break;
      default:
        throw new Exception("scaleBitmap unforeseen mode $mode");
    }
    return $bitmap;
  }

  private function photoToPath($photo, $mode) {
    switch ($mode) {
      case "full":
        $pathName = self::DB_PHOTOS_PATH_FULL;
        break;
      case "thumbnail":
        $pathName = self::DB_PHOTOS_PATH_THUMBNAIL;
        break;
      default:
        throw new Exception("photoToPath unforeseen mode $mode");
    }
    return
      $pathName . 
      $photo["id_person"] .
      "/" .
      $photo["name"] .
      "." .
      $photo["type"]
    ;
  }

   /**
   * Check for photo duplication
   *
   * @param  array: photo
   * @param  string: photo sum
   * @return boolean: true    if photo is a duplicate
   *                  false   if photo is not a fuplicate
   */
  private function checkPhotoDuplication($photos, $sum) {
    if (!$photos) {
      return false;
    }
print "PHOTOS:\n"; var_dump($photos);
    foreach ($photos as $photo) {
      if ($photo["sum"] === $sum) {
        return true;
      }
    }
    return false;
  }

   private function getTypeFromUrl($url) {
    $url_components = parse_url($url); // parse the URL
    $url_path = $url_components['path']; // get the path component
    $ext = pathinfo($url_path, PATHINFO_EXTENSION); // use pathinfo()
    return $ext;
  }

  private function getUrlContents($url) {
    $user_agent = "Mozilla";
    $ch = curl_init();
    if (($errno = curl_errno($ch))) {
      $this->router->log("error", "can't initialize curl, " . curl_strerror($errno));
      throw new Exception("can't initialize curl, " . curl_strerror($errno));
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $output = curl_exec($ch);
    if (($errno = curl_errno($ch))) {
      $this->router->log("error", "can't execute curl to [$url], " . curl_strerror($errno));
      throw new Exception("can't execute curl to [$url], " . curl_strerror($errno));
    }
    curl_close($ch);
    return $output;
  }

  /**
   * Destructor
   */
  function __destruct() {
  }

}