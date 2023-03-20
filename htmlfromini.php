<?php
/*
  This script generates the editors just like the
  original htmlbuilder.
  
  The difference is that this on uses an ini style for
  input so that you can control the html element types and
  label text.
*/
  //HTML cleaner
  require 'beautify_html.php';

  //DB Connection
  require 'dbsetup.php';

  $table = '';                //The current table name
  $title = '';                //The page title string
  $rbycol = '';               //The column for the readby method
  $pagelist = array();        //List of pages for the menu builder.
  $form = '';                 //The form/page content
  $pk = 'id';                 //All tables must have id as their primary key
  $nl = "\n";                 //New line...
  $columns;                   //Column defs for the current table
  $mode = '';                 //single or all
  $ini  = '';                 //The loaded ini file
  $page = '';                 //Currently being built page
  $arg = '';                  //The command line argument
  $currentRow = [];           //Line being processed
  $currentTable = '';         //Table being processed
  
  $msg = '';  //Response message

  global $table,$title,$msg,$db,$form,$nl;

  //Should be one argument
  if (!isset($argv[1]))
  {
    echo "Expecting one argument. None entered..\n\n";
    echo "Enter \"ini\" to generate the ini file from the database.\n";
    echo "Enter \"generate\" to create the editor pages.\n\n";
    exit();
  }
    
  $arg = $argv[1];

  //What to do, what to do...
  if ($arg == 'ini')
  {
    makeIniFile();
    exit();
  }elseif ($arg == 'generate'){
    process();
    exit();
  }else{
    echo "No valid argument entered. Try again.\n";
    exit;
  }
  
  function process()
  {
    global $msg;
    loadIniFile();        //Load the "ini" file of tables,names, ty
    generateEditors();
    makeMenu();
    echo $msg;
  }
  
  function loadIniFile()
  {
    global $ini, $arg;
    $ini = file('htmlbuilder.ini');
    if(!$ini)
    {
      echo "Failed to load the ini file. Exiting.";
      exit();
    }
    
  }
 
/*
  Make a pass through the "ini" file.
  A line will be one of 2 kinds:

  1. [tablename] starts a bew page.

  2. column name,column type,[html type],[label text] 
     defines an html element to add for a column.
  
  This function evaluates the line content and invokes
  the proper handler.
*/ 
  function generateEditors()
  {
    global $ini,$needToFinishTable,$currentItem, $currentTable, $table;
    
    foreach($ini as $line)
    {
      //Parse out the line
      $currentItem = explode(',', $line);
      
      //First table?
      if ($currentTable == '')
      {
        $table = $currentItem[0];
        $currentTable = $currentItem[0];
        startNewTable($currentItem[0]);
      }elseif($currentItem[0] != $table){ //Or new table?
        completeCurrentPage();
        $table = $currentItem[0];
        startNewTable($currentItem[0]);
      }
        
      //Add this column to the page
      addAnElement();
    }
    
    //Finish up the last page
    completeCurrentPage();
  }
  
  function startNewTable()
  {
    global $needToFinishTable,$page,$nl,$table, $currentTable, $currentItem;
    
    //Get the table name from the curreht line item
    $table = $currentItem[0];
        
    echo "Starting page for table: $table $nl";
    
    //Start the page - HTML and head, form beginning and command buttons
    
    $page  = file_get_contents('templates/pagehead.html') .$nl;
    $page .= file_get_contents('templates/beginform.html') .$nl;
    $page .= file_get_contents('templates/buttons.html') .$nl;
    $page  = str_replace('[table]', $table, $page);
    
    //Set the page title and caption
    $title = ucfirst($table) . ' Editor';
    $page  = str_replace('[title]', $title, $page);
  }
  
  //Complete and save the currently building page
  function completeCurrentPage()
  {
    global $page,$table,$msg,$nl,$pagelist;
    
    $page .= "      </form>\n";
    $page .= "    </div>\n";
    $page .= file_get_contents('templates/footLinks.html') .$nl;
    //$form = $cleanup($form);
    
    $fname = './forms/'.$table.'Editor.html';
    if(!file_put_contents($fname, $page))
    {
      echo "Failed to save $fname.$nl";
    }
   // $fname = "'$fname'";
    $pagelist[] = $table.'Editor.html';      //For Menu page
    $msg .= "Generated $fname for table $table $nl";
  }
    
  //Extract the line parts, adjust for blank parts
  //and add an element to the page.
  // table,column,type,[htmltype],[label test]
  //Array $curentItem holds the values
  function addAnElement()
  {
    global $page,$currentItem;

    $column = '';
    $type = '';
    $element = '';
    $label = '';

    //The column name is always first
    $column = $currentItem[1];
    $type = $currentItem[2];
    $element = $currentItem[3];
    $label = ucFirst($currentItem[4]);
    
    //Get the proper template
    $template = loadElementTemplate($element);
    
    //Substitute the tags
    $template = str_replace('[name]', $column, $template);
    $template = str_replace('[lbl]', $label, $template);
    
    //Append the element code to the page
    $page .= $template."\n";
  }
  
  //Load the template for the element type
  //If no template, load the default
  function loadElementTemplate($element)
  {
    $templatename = 'templates/'.$element.'.html';
    if (file_exists($templatename))
    {
      $template = file_get_contents($templatename);
    }else{
      $t = translateFieldType($element);
      $templatename = 'templates/'.$t.'.html';
      if (file_exists($templatename))
      {
        $template = file_get_contents($templatename);
      }else{
        
        $template = file_get_contents('templates/input.html'); //Default is the generic input
      }
    }
    return $template;
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
     $pren = strpos($coltype, '(');
     if ($pren)
     {
       $coltype = substr($coltype,0,$pren);
     }
     
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
    echo "Generated Menu page $fname $nl";
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
    function stripPrens($coltype)
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

