<?php
//Simple server to serve editor crud requests
  //All db service comes from the CRUD class.
  require '../assets/vendor/autoload.php';
  require 'ezsqlcrud.php';
  $crud = new CRUD();
  
  //Parse the query string to get the form values.
  $vals = [];
  parse_str($_SERVER['QUERY_STRING'], $vals);

  //Invoke the crud driving method
  $res =  $crud->go($vals);
  
  $resulttype = gettype($res);
  switch($resulttype)
  {
    case 'array':
      echo(json_encode($res));
      break;
    case 'string':
      echo $res ."\n";
      break;
    case 'boolean':
      if($res)
        echo "> Request Succeded.\n";
      else
        echo ">Request Failed.\n";
      break;
    case 'NULL':
      echo ">No result returned.\n";
      break;
    
    default:
      echo ">Unknown Response: $resulttype \n";
      break;
  }
      
      
  
  
  