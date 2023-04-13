# DB-Table-Editor-Generator
Command line tool that creates a web page editor for every table in an SQL database

This project is a command line tool that generates a a full CRUD database table editor 
HTML page for each table in an SQL database and an index.html menu page to access them.  

Each editor has has a complete set of CRUD services plus read first, last, next and previous.

No muss, no fuss.

#### Installation:

1. Download the code and unzip it to a folder of your localhost.  
I use the **WAMP** stack so it lives in the **WWW** folder.
2. Edit the **dbsetup.php** file and edit these fields for your environment:  

         $servername = "localhost";  
         $username   = " ";  
         $password   = " ";  
         $dbname     = " "; 
         
3. Edit the **ezsqlsetup.php** file in the forms/bin folder the same way.

#### Usage:

Using the command prompt of your choice:
1. Navigate to the folder you installed in.
2. Type "php htmlbuilder.php" and press enter - no quotes...

The pages are generated into the forms folder and a list of files is displayed.    
That's it.  


##### Warning: The new pages replace existing pages in the forms folder. Be aware of this if you make changes to these files.

For this reason I like to copy the forms folder someplace else maybe with a different more meaningful
name. The new folder is stand alone.

Open your browser and Navigate to the folder and the menu page (index.html) should open.

#### DB Requirements:

This tool uses the **ezsql** repo. from Github.   
Ezsql is the database class used by Wordpress which would seem to speak highly of it...

I have been a fan of this class for a long time.  

The author says:  

"A class to make it very easy to deal with database connections. An universal interchangeable CRUD system."

It is also very fast.  

Having said that, here are the DB oriented things you need to be aware of.  

1. All tables must have their primary key named "id".

2. The generated HTML contains a form that contains a few hidden inputs:  
   - **opcode** carries the method code for the requested DB operation.
   - **table** carries the name of the table that the form is for.
   - **readbycolumn** carries the name of the column used by the crud read method which is actually a select* where column = value.  
   This input determines that column and it's value from the from the row values. The first column after the PK is used if not provided.
   - **id** carries the current rows PK value.      
   **Do not change these or the CRUD services won't work.**
   
   I mention these elements in case you want to provide your own templates. See below...
3. The page captions are table name + "Editor."  

Ezsql accomodates several other sql databasess such
   
###### Start of the Form code:   
```
      <form id="form1" name="form1"> 
         <!-- The requested server opertion -->
         <input type="hidden" id="opcode" name="opcode"/>
         <!-- The table being edited -->
         <input type="hidden" id="table" name="table" value="[table]"/>
         <!-- The column to do readby on -->
         <input type="hidden" id="readbycolumn" name="readbycolumn" value="[readbycolumn]"/>

         <!-- The DB table record PK - id -->
         <input type="hidden" id="id" name="id"/>
```         

#### Templates:

The tool uses a set of templates that it loads and does simple string replacement on embedded tags.

As each template is needed it is added to the page being built using file_get_contents() and str_replace() is used to substitute the tags. 

The templates that are included use my own code style. Please read the templates and feel free to alter them to fit your needs.

Do not alter the tags on the beginform.html template as the project's crud class depends upon them.   
Also see file definitions and the tag definitions below. 

#### Template File Contents:

**beginform.html** is the start of the form - see the code sample above.  
**buttons.html** is the command buttons defs. - one for each available operation.  
**footlinks.html** is the Javascript links at the end of the page and the closing page tags.  
**input.html** is the input tag skeleton.  
**pagehead.html** is the HTML page top with the HTML and head tags plus meta, title and css tags.  
**textarea.html** is the skeleton for textarea tags.

This is a somewhat limited set of inputs but for this purpose it should be ok.   
I intend to create a more general generator as time permits.

There are a few more templates that are used to generate the menu page HTML. 

#### Tags Used:  

The tags are simple - [xxxx].

**[name]** tags the label for= and id= and name= elements with the column name.  

**[lbl]**  tags the label text with capitalized column names.

**[title]** tags the HTML title element and a page top title. 

**[readbycolumn]** tags the column used in the read method.

#### Other Considerations:

The tool only puts inputs and textareas in the HTML code.  
While this may seem like a hinderance, all fields in the underlying  
table are accounted for and you can do edits for columns that you  
like to have inputs like 
radios and checkboxes.  

I have given thought to something like a text file list of column  
names and HTML elements to make for them. It would be a little effort  
on your part but would open things up a bit.

Ezsql acomodates several popular databases. The database type is specified in the connection string.  
See the WIKI. 

1. MySQLi
2. PDO MySQL
3. PDO pgSQL
4. PDO SQLite
5. PDO SQLSRV
6. PostgreSQL
7. SQLite
8. SQLSRV

#### A Different Way:

The  second script included - htmlfromini.php - is an alteration of htmkbuilder.php . 

It requires a bit more effort on your part but adds a lot more flexability.

This version uses a pseudo ini like file that the script generates for you.

The generated file contains one line for every column in every table in the DB.

The line is of the form "table name,column name,column type,html type,label text".  

As generated the html type is the column type and the label text is the capitalized column name.

The idea is that you can go throsugh this file and replace the html type and/or label text with
what is more to your liking.

Look in the templates folder. The file names there indicate the html code they contain.  
For instance, if you wanted a column representing a date you could use "date" for the html type and date.html would
be the tempate used for it.



Optionally you can add label text for the element after the type. For example:  

"email,varchar,,"  you would add email,Your Email. The result is an email input with a label "Your Email."

You feed the file to the PHP script and it generates the editors just like htmlbuilder using
your element types and labels.

If you leave the type empty the same code as htmlbuilder.php uses is used.

If you leave the label field empty, the capitalized column name is used.

###### Usage:

1. Generate an ini file using: php htmlfromini.php ini

2. Examine the "ini" file and make any changes.

3. Generate the editors: php htmlfromini.php generate


In conclusion, Please try this tool and peruse the code and templates and let me know what you think about it - good or bad. Also suggest chnages and addittions.
