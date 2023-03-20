<?php
/*
  This script collects some options from the user 
  and creates acomplete HTML editor page for 
  a specified database table or all tables in a
  database.
  
  Before use, edit the dbsetup.php file andset your db info.
*/
  //HTML cleaner
  require 'beautify_html.php';

  //DB Connection
  require 'dbsetup.php';

  $table = '';    //The current table name
  $title = '';     //The page title string
  $rbycol = '';   //The column for the readby method
  $pagelist = array(); //List of pages for the menu builder.
  $form = '';     //The form/page content
  $pk = 'id';     //All tables must have id as their primary key
  $nl = "\n";     //New line...
  $columns;       //Column defs for the current table
  $mode = '';     //single or all
  
  $msg = '';  //Response message

  global $table,$title,$msg,$db,$form,$nl;
  
  //Ini file generation requested?
  if (isset($argv[1]) and $argv[1] == 'ini')
  {
    makeIniFile();
    exit();
  }

  //Run it...
  makeAll();    //Generate the pages
  makeMenu();   //Generate the menu page
  echo $msg .$nl;
  exit();

  //Generate a page for every table.
  function makeAll()
  {
    global $db,$title;
    $query = $db->query('show tables');
    $tables =  $query->fetchAll(PDO::FETCH_COLUMN);
    foreach($tables as $table)
    {
      $title = ucfirst($table).' '.'Editor';
      make($table);
    }
  }
  
  //Generate a page for one table
  function make($table)
  {
    if(!getColumns($table)) return;
    return generateTheForm($table);
  }
  
  //This gets a list of the tables column info
  function getColumns($table)
  {
    global $db, $msg,$columns;
    $q = $db->prepare("SHOW COLUMNS FROM $table");
    try{
      $q->execute();
    }catch (exception $e){
      $msg .= "Unable to read table columns for table $table. Message:\n". $e->getMessage();
      return false;
    }
    $columns = $q->fetchAll();
    return true;
  }
  
  //This is the main function
  function generateTheForm($table)
  {
    global $title,$msg,$db,$form, $nl,$columns,$rbycol, $mode,$pagelist;
    
    //Start the page - HTML and head, form beginning and command buttons
    $form = '';
    $form .= file_get_contents('templates/pagehead.html') .$nl;
    $form .= file_get_contents('templates/beginform.html') .$nl;
    $form .= file_get_contents('templates/buttons.html') .$nl;

    //Set the titles, the table selection and the read where column

    //Add the titles for single table requests
    $form = str_replace('[title]', $title, $form);
    
    //Add the table name to the form hidden input -->
    $form = str_replace('[table]', $table, $form);
    
    //Set the readby column to the first column after the PK.
    $col = $columns[1];
    $rbycol = $col['Field'];
    $form = str_replace('[readbycolumn]', $rbycol, $form);

    //Make the HTML for the columns
    foreach($columns as $col)
    {
      $colname = $col['Field'];
      $coltype = $col['Type'];
      
      if($colname == 'id') continue;    //Skip the PK
      
      //Get the proper skeleton template
      if ($coltype == "blob" || $coltype == "text")
      {
        $skel = file_get_contents('templates/textarea.html') .$nl;
      }else{
        $skel = file_get_contents('templates/input.html') .$nl;
      }
      
      //Replace the [name], [lbl] and [type] tags in the skeleton
      $skel = str_replace('[name]', $colname, $skel);
      $skel = str_replace('[lbl]', ucfirst($colname), $skel);
      $inputtype = translateFieldType($coltype);
      $skel = str_replace('[type]', $inputtype, $skel);
      $form .= $skel."\n";
    }
    
    //Complete the page
    $form .= "      </form>\n";
    $form .= "    </div>\n";
    $form .= file_get_contents('templates/footLinks.html') .$nl;
       
    //Save it
    $fname = './forms/'.$table.'Editor.html';
    file_put_contents($fname, $form);

    $pagelist[] = $table.'Editor.html';      //For Menu page maker
    
    $msg .= "Generated $fname for table $table.$nl";
  }
  
  //Translate the column type tp an input type.
  //This is probably going to produce some mistakes. Watch it!
   function translateFieldType($coltype)
  {
    //Data types by input types - may need work
    $anumber   = array('bigint','int','smallint','tinyint','bit','decimal','numeric','money','smallmoney','float','real');
    $adate     = array('datetime','smalldatetime','time', 'data');
    $atext     = array('char','varchar','varcharmax','nchar','nvarchar','nvarcharmax','ntext');
    $atextarea = array('text','blob','binary','varbinary','varbinarymax','image');

     //Remove parenthasese
     $pren = strpos('(',$coltype);
     if ($pren)
     {
       $coltype = substr($coltype,0,$pren);
     }
     
     //Return the type
     if (in_array($coltype, $anumber))   return 'number';
     if (in_array($coltype, $adate))     return 'date';
     if (in_array($coltype, $atext))     return 'text';
     if (in_array($coltype, $atextarea)) return 'textarea';
     return 'text';
  }
  
  
  function cleanup($form)
  {
    $beautify = new Beautify_Html(array(
      'indent_inner_html' => false,
      'indent_char' => " ",
      'indent_size' => 2,
      'wrap_line_length' => 32786,
      'unformatted' => ['code', 'pre'],
      'preserve_newlines' => false,
      'max_preserve_newlines' => 32786,
      'indent_scripts'	=> 'normal' // keep|separate|normal
    ));

    return $beautify->beautify($form); 
  }
  
  //Generate the menu page
  //Simple HTML with links to all of the editor pages
  function makeMenu()
  {
    global $pagelist,$nl;
    //Load the page skeleton
    $form = file_get_contents('templates/menu.html') .$nl;
    $form = str_replace('[title]', 'DB Table Editors Menu', $form);        

    //Add a link for each page generated
    foreach($pagelist as $pagename)
    {
        $pnparts = explode('.',$pagename);
        $ltext = $pnparts[0];
        $ltext = ucfirst($ltext);
        $ltext = str_replace('Edit', ' Edit', $ltext);        
        
        $link = file_get_contents('templates/linktemplate.html');
        $link = str_replace('[pg]', $pagename, $link);        
        $link = str_replace('[pgname]', $ltext, $link);        
        $form .= $link . "\n";
    }

    //Finish the page
    $form .= file_get_contents('templates/footLinks.html') .$nl;
    
    $fname = './forms/index.html';
    file_put_contents($fname, $form);
  }
  
/*
  Generate a pseudo ini file of the database.
  each line is table,column,type,[html tag],[optional label text]
  with a line for each column like colname,type,
  The user can enter html types for each column
  like "option,varchar," and the user would add "text"
  To call for a text input.
  The file would be used as input to a variant of this tool.
*/  
  function makeIniFile()
  {
    global $db,$columns,$nl;
    $ini = '';
    //Get a list of tables in the database
    $query = $db->query('show tables');
    $tables =  $query->fetchAll(PDO::FETCH_COLUMN);
    
    //Add an ini section for each table
    foreach($tables as $table)
    {
      
      
      getColumns($table);
      
      foreach ($columns as $col)
      {
        $colname = $col['Field'];
        if ($colname == 'id') continue; //skip the PK
        
        $coltype = stripPrens($col['Type']);
       
        $ini .= "$table,$colname,$coltype,$coltype,$colname$nl";
      }
      //$ini .= $nl;
    }
    
    //Save the file
    file_put_contents('htmlbuilder.ini', $ini);
    echo "Made ini file htmlbuilder.ini.$nl";
  }

    
    //Strip the () in the column type 
    //if present
    function stripPren($coltype)
    {
      $i = strpos($coltype, '(');
      if ($i === false)
      {
        return $coltype;
      }else{
        return substr($coltype,0,$i);
      }
    }
  

  //Send the message variable
  function response($s)
  {
    echo($s);
    exit();
  }

