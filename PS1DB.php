<?php
include("inc.cURL.php");
$db = new SQLite3('PS1.db');

//ns348841.ip-91-121-109.eu/psxdata/ulist.html
$source="psxdatacenter.com";


function convert_utf($text,$out="ISO-8859-1//TRANSLIT//IGNORE") {
    $first2 = substr($text, 0, 2);
    $first3 = substr($text, 0, 3);
    $first4 = substr($text, 0, 3);
    
    if ($first3 == chr(0xEF).chr(0xBB).chr(0xBF)) return iconv("UTF-8", $out, $text);
    elseif ($first4 == chr(0x00).chr(0x00).chr(0xFE).chr(0xFF)) return iconv("UTF-32BE", $out, $text);
    elseif ($first4 == chr(0xFF).chr(0xFE).chr(0x00).chr(0x00)) return iconv("UTF-32LE", $out, $text);
    elseif ($first2 == chr(0xFE).chr(0xFF)) return iconv("UTF-16BE", $out, $text);
    elseif ($first2 == chr(0xFF).chr(0xFE)) return iconv("UTF-16LE", $out, $text);
    else return $text;
}


parse_ps1db($db,$source,"u");
parse_ps1db($db,$source,"p");
parse_ps1db($db,$source,"j");

function table_create($db,$name){
    echo("Creating Table: ".$name."\n");
    
    $db->query('CREATE TABLE `'.$name.'` (
    `id`	TEXT,
    `forward`	TEXT,
    `name`	TEXT,
    `lang`	TEXT,
    `region`	TEXT,
    `genre`	TEXT,
    `diskx`	INTEGER,
    `diskn`	INTEGER,
    `publ`	TEXT,
    `date`	TEXT,
    `desc` TEXT);');
}

function table_exist($db,$table){
    return(@$db->query("SELECT * FROM ".$table));
}

function ps1_id_exist($db,$table,$id){
    $res=$db->query("SELECT * FROM ".$table." WHERE id='".$id."'");
    return($res->fetchArray()); 
}

function ps1_table_check($db,$name){
    if(!table_exist($db,$name)){table_create($db,$name);}
}

function parse_ps1db($db,$source,$list){
    $mdata=convert_utf(HTTP_GET($source."/".$list."list.html"));
    global $source;
    $pos=0;
    $output=[];

    while(strpos($mdata, "\"col1\"",$pos)!==false){
        $info=false;
        $ignore=false;
        
        $pos = strpos($mdata, "\"col1\"",$pos);
        $pos = strpos($mdata,"href=\"",$pos)+6;
        $gurl=substr($mdata, $pos, strpos($mdata, "\"",$pos)-$pos);
        //check if url starts with games, else no info page
        if(substr($gurl,0,5)=="games"){
            $info=true;
        }
        
        //Parse Game ID
        $pos = strpos($mdata, "\"col2\"",$pos);
        $pos = strpos($mdata, ">",$pos)+1;
        $id=substr($mdata, $pos, strpos($mdata, "</td>",$pos)-$pos);
        //Make Game ID array
        if (strpos($id,'<br>') !== false) {
            $id=explode("<br>",$id);
        }else{
            $id=array($id);
        }
        
        //Check for Special infopage condition (GTA:CE)
        $tmp = explode("/",$gurl);
        $tmp = $tmp[count($tmp)-1];
        //check Collection ID against Disc ID
        $coid=substr($tmp,0,-5); // Strip ".html"
        if($id[0] !== $coid){
            //If IDs do not match, ignore as Disc IDs are already in database.
            $ignore=true;
        }
        
        if(!$ignore){
            $pos = strpos($mdata, "\"col3\"",$pos);
            $pos = strpos($mdata, ";",$pos)+1;
            $t=strpos($mdata, "<",$pos);
            $t2=strpos($mdata, "&",$pos);
            if($t2<$t){
                $t=$t2;
            }
            $name=substr($mdata, $pos, $t-$pos);
            
            if($info){
                
                $pos = strpos($mdata, "\"col4\"",$pos);
                $pos = strpos($mdata, ";",$pos)+1;
                $t=strpos($mdata, "<",$pos);
                $t2=strpos($mdata, "&",$pos);
                if($t2<$t){
                    $t=$t2;
                }
                $lang=substr($mdata, $pos, $t-$pos);
        
                //Download info page
                $gurl=$source."/".$gurl;
                $game=convert_utf(HTTP_GET($gurl));

                $gpos = strpos($game, "Official Title");
                $gpos = strpos($game, "&nbsp;",$gpos)+6;
                $title=substr($game, $gpos, strpos($game, "<",$gpos)-$gpos);

                $gpos = strpos($game, "Region");
                $gpos = strpos($game, "&nbsp;",$gpos)+6;
                $region=substr($game, $gpos, strpos($game, "<",$gpos)-$gpos);

                $gpos = strpos($game, "Genre");
                $gpos = strpos($game, "&nbsp;",$gpos)+6;
                $ggenre=substr($game, $gpos, strpos($game, "<",$gpos)-$gpos);
                $genre=trim(str_replace("&nbsp;"," ",$ggenre));

                $gpos = strpos($game, "Developer");
                $gpos = strpos($game, "&nbsp;",$gpos)+6;
                $gdev=substr($game, $gpos, strpos($game, "<",$gpos)-$gpos);
                $dev=trim(str_replace("&nbsp;"," ",$gdev));

                $gpos = strpos($game, "Publisher");
                $gpos = strpos($game, "&nbsp;",$gpos)+6;
                $gpub=substr($game, $gpos, strpos($game, "<",$gpos)-$gpos);
                $pub=trim(str_replace("&nbsp;"," ",$gpub));

                $gpos = strpos($game, "Date Released");
                $gpos = strpos($game, "&nbsp;",$gpos)+6;
                $grdate=substr($game, $gpos, strpos($game, "<",$gpos)-$gpos);
                $rdate=trim(str_replace("&nbsp;"," ",$grdate));

                $gpos = strpos($game, "<!-- Game Description Sectional -->");
                $gpos = strpos($game, "Manufacturer",$gpos);
                $gpos = strpos($game, "<br>",$gpos)+4;
                $gdesc=substr($game, $gpos, strpos($game, "</td>",$gpos)-$gpos);
                $gdesc=str_replace("&nbsp;"," ",$gdesc);
                $gdesc=trim($gdesc);

                //formatting
                $gdesc=str_replace("<br>","",$gdesc);
                $gdesc=str_replace("<u>","",$gdesc);
                $gdesc=str_replace("</u>","",$gdesc);
                $gdesc=str_replace("<li>"," * ",$gdesc);
                $gdesc=str_replace("</li>","",$gdesc);
                $gdesc=str_replace("<ul>","",$gdesc);
                $desc=str_replace("</ul>","",$gdesc);
            }

            $i=1;
            foreach($id as $gid){
                $gid = explode("-",$gid);
                ps1_table_check($db,$gid[0]);
                if(!ps1_id_exist($db,$gid[0],$gid[1])){
                    if($info){
                        if($i==1){
                            echo($id[0]." - ".$title."\n");
                            $db->query("INSERT INTO ".$gid[0]." VALUES ('".$gid[1]."',0,'".SQLite3::escapeString($title)."','".$lang."','".$region."','".SQLite3::escapeString($genre)."',".$i.",".count($id).",'".SQLite3::escapeString($pub)."','".SQLite3::escapeString($rdate)."','".SQLite3::escapeString($desc)."');");
                        }else{
                            echo("Forwarding ".$id[$i-1]." to ".$id[0]."\n");
                            $db->query("INSERT INTO ".$gid[0]." VALUES ('".$gid[1]."','".substr($id[0],5)."','','','','',".$i.",".count($id).",'','','');");
                        }
                    }else{
                        echo($id[$i-1]." ! No Info Found\n");
                        echo($id[$i-1]." - ".$title."\n");
                        $db->query("INSERT INTO ".$gid[0]." VALUES ('".$gid[1]."',0,'".SQLite3::escapeString($title)."','','','',".$i.",".count($id).",'','','');");
                    }
                    $i++;
                }else{
                    echo($id[0]." ! Skipped, Already Exists\n");
                }
            }
        }
    }
}

?>