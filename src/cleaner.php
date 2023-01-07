<?php
error_reporting( E_ALL );
ini_set( 'display_errors' , 1 );

require_once( __DIR__ . '/php/xmlrpc.php' );
require_once( __DIR__ . '/php/util.php' );

// -- Vars ----------------------------------------------------------------------------------------

$User = getUser();

if ( !$User )
{

    die( 'Permission Denied' );

}

$Directory = rTorrentSettings::get()->directory;

$DirectorySeparator = DIRECTORY_SEPARATOR;

// -- Funktionen ----------------------------------------------------------------------------------

function mksize( $bytes = 0 )
{

    if ( $bytes < 1000 * 1024 )
    {
    
        return number_format( $bytes / 1024 , 2, ',', '.' ) . ' KB';
    
    }
    elseif ( $bytes < 1000 * 1048576 )
    {
    
        return number_format( $bytes / 1048576, 2, ',', '.' ) . ' MB';
    
    }
    elseif ( $bytes < 1000 * 1073741824 )
    {
    
        return number_format( $bytes / 1073741824, 2, ',', '.' ) . ' GB';
    
    }
    else
    {
        
        return number_format( $bytes / 1099511627776, 2, ',', '.' ) . ' TB';

    }
        
}

// -- POST / GET ----------------------------------------------------------------------------------

if ( isset( $_REQUEST['delete'] ) )
{
    
    foreach ( (array)$_REQUEST['delete'] AS $File )
    {
        
        $File = urldecode( $File );

        if ( file_exists( $Directory . $DirectorySeparator . $File ) )
        {
        
            exec( 'rm -R ' . escapeshellarg( $Directory . $DirectorySeparator . $File ) );
            
        }
    
    }
    
    header( 'Location: cleaner.php' );
    
}

// -- hole Transfers ------------------------------------------------------------------------------

$cmd = new rXMLRPCCommand( 'd.multicall', 'main' );

$cmd->addParameters( 'd.get_base_path=' );
        
$req = new rXMLRPCRequest($cmd);
        
if ( !$req->success() || !isset( $req->val ) || !is_array ( $req->val ) )
{

    die( 'rXMLRPCRequest failed' );

}

$TorrentsExists = array_filter( $req->val );

// -- hole existierende Files ---------------------------------------------------------------------
        
exec( 'ls ' . escapeshellarg( $Directory ), $FilesExists);

array_walk($FilesExists, function(&$item) 
{ 
    global $Directory, $DirectorySeparator; 

    $item = $Directory . $DirectorySeparator . $item; 
});

// ------------------------------------------------------------------------------------------------

$UselessFileArr = array_diff( $FilesExists, $TorrentsExists );

// ------------------------------------------------------------------------------------------------

$SumFileSize = 0;
$SumFiles  = 0;
$FileList = '';
foreach ( $UselessFileArr AS $UselessFile )
{    

    $File = explode( $DirectorySeparator, $UselessFile );

    $Name = end( $File );
    
    unset( $FileSize );
    
    exec( 'du -sb ' . escapeshellarg( $UselessFile ) . ' | cut -f1' , $FileSize );
    
    $Ico = 
        is_dir( $UselessFile ) ?
            '<img style="vertical-align:top;" title="Ordner" src="images/dir.gif" alt="Folder" />' 
        : 
            '<img style="vertical-align:middle;" title="Datei" src="images/file.gif" alt="File" />';
        
    $FileList .= 
    '<div class="fileList">
      <div class="ico">' . $Ico . '</div>
      <div class="files" title="' . htmlentities( $UselessFile ) . '">' . ( strlen( $Name ) > 93 ? substr( htmlentities( $Name ), 0, 90 ) . '...' : htmlentities( $Name ) ) . '</div>
      <div class="filesize">' . mksize( $FileSize[0] ) . '</div>
      <div class="options">
        <a class="delete" href="cleaner.php?delete=' . urlencode( $Name ) . '"><img src="images/blank.gif" alt="X" /></a>
        <input type="checkbox" value="' . $Name . '" name="delete[]" class="delFile" />
      </div>
      <div class="clear"></div>
    </div>';

    $SumFileSize += $FileSize[0];
    $SumFiles++;
    
}

if (empty($FileList))
{
    
    $FileList = 
    '<div class="fileList">
      Keine überflüssigen Dateien gefunden 
      <img alt="" style="vertical-align:bottom;" src="data:image/gif;base64,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" />
    </div>';
    
}

// -- Ausgabe -------------------------------------------------------------------------------------

echo
'<!DOCTYPE HTML>
<html>
<head>
  <title>RTorrent Cleaner</title>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  <meta name="publisher" content="b-t-g" />
  <meta name="author" content="ike" />
  <link type="text/css" rel="stylesheet" href="css/cleaner.css" />
  <script type="text/javascript" src="js/jquery.js"></script>
  <script type="text/javascript" src="js/cleaner.js"></script>
</head>
<body>

<form action="cleaner.php" method="post" id="delForm">
  <div class="contentTable">
    <div class="titleBar">RTorrent Cleaner von ' . $User . '</div>
    ' . $FileList . '
    <div class="founds">' . $SumFiles . ' Dateien mit einer Gesamtgröße von ' . mksize($SumFileSize) . ' gefunden.</div>
    <div class="multiDelete">
      <input id="delSubmit" type="submit" value="Ausgewählte Daten löschen" />
      <label for="selAll">Alles auswählen</label> <input type="checkbox" name="selAll" value="1" id="selAll" />
    </div>
    <div class="clear"></div>
    <div class="credits">&copy;2013, ike</div>
  </div>
</form>

</body>
</html>';
