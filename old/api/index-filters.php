/*
private function getFilters($type, $params, $request) {
  $filters = [];   
  $paramslist = [];
  foreach ($params as $name) {
    $value = $request->params($name);
    if ($type == "range") {
      $rangesep = "-";
      if (strstr($value, $rangesep)) {
        list($filters[$type][$name]["min"], $filters[$type][$name]["max"]) = explode($rangesep, $value);
      }
    }
  }
  return $filters;
}
*/