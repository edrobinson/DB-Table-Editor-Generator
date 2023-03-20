<?php
/*

  CRUD class using ezsql
  
  This isidentical to the original except 
  that it uses the ezSQL class for database access.
  
  The ezSQL access takes place in ezsqlsetup.php
  and in the various methods here. They are much
  more abstracted away from the PHP MySQL functions
  and make for cleaner code.

  ezSQL also makes it insanely easy to use different sql databases.
  
  See https://github.com/ezSQL/ezsql
  
*/
class CRUD{
  public $table;
  public $db;

  //Table info
  public $pk = 'id';
  public $columns;

/*  
  The constructor jst does the DB connection
*/  
  public function __construct()
  {
    $this->db_connect();
  }
  
  //Create the DB connection.
  public function db_connect()
  {
    require 'ezsqlsetup.php';
    $this->db = $db;
 }
  
  //This gets a list of the tables column names.
  public function getColumns()
  {
    $this->columns = array();
    $qry = "select * from $this->table where id > 1 limit 1";
    $res = $this->db->get_row($qry);

    foreach ( $this->db->get_col_info("name")  as $name )
    {
        array_push($this->columns, $name);
    }

  }
  
/*
  Scan the input array and makea new array of only
  the table's column values.
  
  Called by insert and update methods.
  
  This means that your form can contain fields
  other thanthose beloging to the underlying
  table and there is no chance of them being
  added to the table altering methods.
*/  
  public function getColumnValues($row)
  {
    $vals = [];
    foreach($row as $key=>$val)
    {
      if (in_array($key, $this->columns))
        $vals[$key] = $val;
    }
    return $vals;
  }
  
  //Set the table name and id vars, get the table columns
  // and invoke the requested operation all of which do
  //the return to the caller.
  public function go($row)
  {
    $this->table = $row['table'];   //The name of the DB table to access.
    $this->getColumns();            //Load the table's column definitions.
    $id = $row['id'];               //The PK value for the row.
    
    switch ($row['opcode']){
        case 'read':
            return $this->read($id);
            break;
        case 'readby':
            return $this->readBy($row);
            break;
        case 'update':
            return $this->update($row);
            break;
        case 'insert':
            return $this->insert($row);
            break;
        case 'delete':
            return $this->delete($row['id']);
            break;
        case 'readfirst':
            return $this->readFirst();
            break;
        case 'readprev':
            return $this->readPrev($row['id']);
            break;
        case 'readnext':
            return $this->readNext($row['id']);
            break;
        case 'readlast':
            return $this->readLast();
            break;
        case 'lookup':
            return $this->lookup($row);
            break;
        default:
            return false;
            exit();
    }
 }
  
  //Read on the primary key
  public function read($id)
  {
    $qry = "select * from $this->table where id = '$id'";
    return $this->db->get_row($qry, ARRAY_A);
  }
  
  //Read on a field and it's value
  public function readBy($row)
  {
    $column = $row['readbycode'];
    $value  = $row[$column];
    $qry = "select * from $this->table where $column = '$value' limit 1";
    return $this->db->get_row($qry, ARRAY_A);
  }
  
/*
  Insert a New record
  The input a complete set of form values
  from the client.
*/  
  public function insert($row)
  {
    $vals = $this->getColumnValues($row);

    $fields = '(';
    $values = '(';
    //Build the fields and value lists
    foreach($vals as $key=>$val)
    {
      if($key == 'id') continue;  //Skip the PK field
      $fields .= $key.',';
      $values .= "'$val',";
    }
    //Cleanup and enclose the field list
    $fields = rtrim($fields, ',');
    $fields .= ')';
    //Cleanup and enclose the values list
    $values = rtrim($values, ',');
    $values .= ')';

    $qry = "insert into $this->table $fields values $values";

    $this->db->query($qry);
    if ($this->db->affectedRows() > 0)
    {
      return true;
    }else{
      return false;
    }
  }
    
  //update the table given an array(field=>value)
  public function update($row)
  {
    $row = $this->getColumnValues($row);
    $i = 0;
    $key = '';
    $avals = array();
    $qry = "update $this->table set ";
    foreach($row as $fld => $val)
    {
      //Is this the PK? Save it
      if ($fld == 'id')
      {
          $key = $val;
          continue;
      }
      
      $qry .= "$fld = '$val',";
    }

    //No PK provided?
    if ($key == '')
    {
      return false;
    }
    
    $qry = rtrim($qry, ',');  //Remove the last comma...
    
    $qry .= " where id = $key";
    $this->db->query($qry);
    if ($this->db->affectedRows() > 0)
    {
      return true;
    }else{
      return false;
    }
  }
  
  //Delete the record with the passed in PK value
  public function delete($id)
  {
    $qry = "delete from $this->table where id = '$id'";
    $this->db->query($qry);
    if ($this->db->affectedRows() > 0)
    {
      return true;
    }else{
      return false;
    }
  }
  
  //Return all records from the table in
  //specified field order
  public function list($orderby)
  {
    $qry = "select * from $this->table order by $orderby";
    return $this->db->get_results($qry);
  }
    
    
  //Lookup is not supported but is in the editor 
  //command button set. 
  public function lookup($row)
  {
    return '>The lookup function is not supported.';
  }
  

/************** Positional Queries **********************/  

    public function readFirst(){
        $qry = "select * from $this->table limit 1";
        return $this->doCrudQuery($qry);
    }
     
   
    public function readLast(){
        $qry = "select * from $this->table order by id desc limit 1";
        return $this->doCrudQuery($qry);
    
    }
    
    public function readNext($id){
        $qry  = "select * from $this->table where id = (select min(id) from $this->table  where id > '$id')";
        return $this->doCrudQuery($qry);
    }
    
    public function readPrev($id){
        $qry  = "select * from $this->table where id = (select max(id) from $this->table where id <  $id)";
        return $this->doCrudQuery($qry);
    }
    
    //Utility:
    //Execute the passed query
    //Return the row or false
    public function doCrudQuery($query)
    {
        //Execute the query
        return $this->db->get_row($query, ARRAY_A);
    }
    
  
  
  
} //End of class