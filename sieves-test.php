<?php

  $all = false;
  list($sqlSieves, $params) = sieves2Sql([ "filters" => [ "active" => "yes" ] ]);
  print("sqlSieves:"); print_r($sqlSieves); print("\n");
  print("params:"); print_r($params); print("\n");
  exit;


  function sieves2Sql($sieves = null) {
    $sql = "";
    $params = [];

    if (
      isset($sieves) &&
      isset($sieves["search"]) &&
      isset($sieves["search"]["term"])
    ) {
      $params["searchTerm"] = $sieves["search"]["term"];
      $sql .= " AND ";
      $sql .= "(
        name LIKE '%' || :searchTerm || '%' OR
        description LIKE '%' || :searchTerm || '%' OR
        phone LIKE '%' || :searchTerm || '%' OR
        zone LIKE '%' || :searchTerm || '%' OR
        street_address LIKE '%' || :searchTerm || '%'
      )";
    }
    if (
      isset($sieves) &&
      isset($sieves["filters"]) &&
      isset($sieves["filters"]["active"])
    ) {
      if ($sieves["filters"]["active"] !== "any") {
        $params["active"] = ($sieves["filters"]["active"] === "yes") ? 1 : 0;
        $sql .= " AND ";
        $sql .= "active = :active";
      }
    }
    if (
      isset($sieves) &&
      isset($sieves["filters"]) &&
      isset($sieves["filters"]["nationality"])
    ) {
      $params["nationality"] = $sieves["filters"]["nationality"];
      $sql .= " AND ";
      $sql .= "nationality = :nationality";
    }
    if (
      isset($sieves) &&
      isset($sieves["filters"]) &&
      isset($sieves["filters"]["voteMin"])
    ) {
      $params["voteMin"] = $sieves["filters"]["voteMin"];
      $sql .= " AND ";
      $sql .= "vote >= :voteMin";
    }
    if (
      isset($sieves) &&
      isset($sieves["filters"]) &&
      isset($sieves["filters"]["commentsCountMin"])
    ) {
      $params["commentsCountMin"] = $sieves["filters"]["commentsCountMin"];
      $sql .= " AND ";
      $sql .= "(SELECT COUNT(*) FROM comment WHERE id_person = person.id) >= :commentsCountMin";
    }
    if (
      isset($sieves) &&
      isset($sieves["filters"]) &&
      isset($sieves["filters"]["age"])
    ) {
      $params["ageMin"] = isset($sieves["filters"]["age"]["min"]) ?
        $sieves["filters"]["age"]["min"] :
        0
      ;    
      $params["ageMax"] = isset($sieves["filters"]["age"]["max"]) ?
        $sieves["filters"]["age"]["max"] :
        PHP_INT_MAX
      ;
      $sql .= " AND ";
      $sql .= "((age IS NULL) OR (age >= :ageMin AND age <= :ageMax))";
    }

    return [ $sql, $params ];
  }

?>