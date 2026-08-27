<html>
<head>
<title>teclado en pantalla</title>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<script language="JavaScript" type="text/javascript">
// Comprueba Navegador y Plataforma del pc
var clientPC = navigator.userAgent.toLowerCase(); // Coge info cliente
var clientVer = parseInt(navigator.appVersion); // Coge versión navegador
 
var is_ie = ((clientPC.indexOf("msie") != -1) && (clientPC.indexOf("opera") == -1));
var is_nav = ((clientPC.indexOf('mozilla')!=-1) && (clientPC.indexOf('spoofer')==-1)
                && (clientPC.indexOf('compatible') == -1) && (clientPC.indexOf('opera')==-1)
                && (clientPC.indexOf('webtv')==-1) && (clientPC.indexOf('hotjava')==-1));
var is_moz = 0;
 
var is_win = ((clientPC.indexOf("win")!=-1) || (clientPC.indexOf("16bit") != -1));
var is_mac = (clientPC.indexOf("mac")!=-1);
 
function imprm(bot) {
var txtarea = document.getElementById('ta');
var tecla = new Array('1','2','3','4','5','6','7','8','9','0','q','w','e','r','t','y','u','i','o','p','a','s','d','f','g','h','j','k','l','ñ','z','x','c','v','b','n','m',',','.','-','\n',' ','              ');
txtarea.value+=tecla[bot];
txtarea.focus();
return;
}
 
function imprM(bot) {
var txtarea = document.getElementById('ta');
var teclaM = new Array('!','"','·','%','/','(',')','=','?','¿','Q','W','E','R','T','Y','U','I','O','P','A','S','D','F','G','H','J','K','L','Ñ','Z','X','C','V','B','N','M',';',':','_','\n',' ','              ');
txtarea.value+=teclaM[bot];
txtarea.focus();
return;
}
 
 
var capa1
var capa2
var ns4 = (document.layers)? true:false
var ie4 = (document.all)? true:false
var ns6 = (document.getElementById)? true:false
 
function teclado() {
   if (ns4) {
     capa1 = document.c1
     capa2 = document.c2
  }
 if (ie4) {
   capa1 = c1.style
   capa2 = c2.style
 }
 if (ns6) {
   capa1 = document.getElementById('c1').style
   capa2 = document.getElementById('c2').style
 }
}
 
function muestra(obj) {
if (ns4) obj.visibility = "show"
else if (ie4) obj.visibility = "visible"
else if (ns6) obj.visibility = "visible"
}
function oculta(obj) {
if (ns4) obj.visibility = "hide"
else if (ie4) obj.visibility = "hidden"
else if (ns6) obj.visibility = "hidden"
}
 
 
function borrar() {
var txtarea = document.getElementById('ta');
if ((clientVer >= 4) && is_ie && is_win) {
var txtSeleccion = document.selection.createRange().text;
 
    if (document.selection) {
 
        if (!txtSeleccion) {
        txtarea.focus();
        var Sel = document.selection.createRange();
        Sel.moveStart ('character', -txtarea.value.length);
        curPos = Sel.text.length;
        txtarea.value=txtarea.value.substr(0,txtarea.value.length-1);
        alert("posicion1: "+curPos);
        return(curPos);
        }
 
        txtarea.focus();
        var Sel = document.selection.createRange();
        document.selection.createRange().text = "";
        Sel.moveStart ('character', -txtarea.value.length);
        curPos = Sel.text.length;
        alert("posicion2: "+curPos);
        return(curPos);
    }
}
 
 
 
 
 
 
else if (txtarea.selectionEnd && (txtarea.selectionEnd - txtarea.selectionStart > 0))
 
    {
 
    var selLargo = txtarea.textLength;
    var selEmpz = txtarea.selectionStart;
    var selFin = txtarea.selectionEnd;
    var s1 = (txtarea.value).substring(0,selEmpz);
    var s2 = (txtarea.value).substring(selFin, selLargo);
    txtarea.value =  s1 +  s2;
    alert("posicion3: "+selEmpz);
    return(selEmpz);    
 
}
else
{
 
    var selLargo = txtarea.textLength;
    txtarea.value = txtarea.value.substr(0,txtarea.value.length-1);
    var Cursor = txtarea.textLength;
    alert("posicion4: "+Cursor);
    return(Cursor); 
 
}
    almznaCursor(txtarea);
}
 
function almznaCursor(textEl) {
    if (textEl.createTextRange) textEl.caretPos = document.selection.createRange().duplicate();
}
 
 
function PosicionCursor(pos) {
var txtarea = document.getElementById('ta');
 
    //Firefox
    if (txtarea .setSelectionRange) {
        txtarea .focus();
        txtarea .setSelectionRange(pos,pos);
    }
    else if (txtarea .createTextRange) {
        var rango = txtarea .createTextRange();
        rango.collapse(true);
        rango.moveEnd('character', pos);
        rango.moveStart('character', pos);
        rango.select();
    }
}
 
function EliminarCaracter()
{
    PosicionCursor(borrar());
}
 
</script>
<style type="text/css">
<!--
.posLay1 {
    position:absolute; 
    visibility:visible; 
    left:10px; 
    top:190px; 
}
 
.posLay2 {
    position:absolute; 
    visibility:hidden; 
    left:10px; 
    top:190px; 
}
 
input{width:20px; height:20px; text-align:center; font-size:10px;}
-->
</style>
</head><body onLoad="teclado();">
 
<form method="post" action="teclado_pantalla.asp">
<textarea id="ta" name="textarea" cols="60" rows="10"></textarea><br>
<br><br>
<br><br>
<br><br>
<br><br>
<input type="submit" id="60" value="ENTER" style="width:60px; height:20px; text-align:center; font-size:10px;"/> 
</form>
 
 
<!-- TECLADO -->
 
<div id="c1" class="posLay1">
<input type="button" id="1" value="1" onclick="imprm(0);" />
<input type="button" id="2" value="2" onclick="imprm(1);" />
<input type="button" id="3" value="3" onclick="imprm(2);" />
<input type="button" id="4" value="4" onclick="imprm(3);" />
<input type="button" id="5" value="5" onclick="imprm(4);" />
<input type="button" id="6" value="6" onclick="imprm(5);" />
<input type="button" id="7" value="7" onclick="imprm(6);" />
<input type="button" id="8" value="8" onclick="imprm(7);" />
<input type="button" id="9" value="9" onclick="imprm(8);" />
<input type="button" id="10" value="0" onclick="imprm(9);" />
<input type="button" id="eliminar_caracter" value="Borrar" onclick="EliminarCaracter()" style="width:40px; height:20px; text-align:center; font-size:10px;" />
<br>
<input type="button" id="11" value="q" onclick="imprm(10);" />
<input type="button" id="12" value="w" onclick="imprm(11);" />
<input type="button" id="13" value="e" onclick="imprm(12);" />
<input type="button" id="14" value="r" onclick="imprm(13);" />
<input type="button" id="15" value="t" onclick="imprm(14);" />
<input type="button" id="16" value="y" onclick="imprm(15);" />
<input type="button" id="17" value="u" onclick="imprm(16);" />
<input type="button" id="18" value="i" onclick="imprm(17);" />
<input type="button" id="19" value="o" onclick="imprm(18);" />
<input type="button" id="20" value="p" onclick="imprm(19);" />
<input type="button" id="41" value="Salto" onclick="imprm(40);"  style="width:40px; height:20px; text-align:center; font-size:10px;" />
<br>
<input type="button" id="21" value="a" onclick="imprm(20);" />
<input type="button" id="22" value="s" onclick="imprm(21);" />
<input type="button" id="23" value="d" onclick="imprm(22);" />
<input type="button" id="24" value="f" onclick="imprm(23);" />
<input type="button" id="25" value="g" onclick="imprm(24);" />
<input type="button" id="26" value="h" onclick="imprm(25);" />
<input type="button" id="27" value="j" onclick="imprm(26);" />
<input type="button" id="28" value="k" onclick="imprm(27);" />
<input type="button" id="29" value="l" onclick="imprm(28);" />
<input type="button" id="30" value="ñ" onclick="imprm(29);" />
<input type="button" id="44" value="Tab" onclick="imprm(42);" style="width:40px; height:20px; text-align:center; font-size:10px;" />
<br>
<input type="button" id="31" value="z" onclick="imprm(30);" />
<input type="button" id="32" value="x" onclick="imprm(31);" />
<input type="button" id="33" value="c" onclick="imprm(32);" />
<input type="button" id="34" value="v" onclick="imprm(33);" />
<input type="button" id="35" value="b" onclick="imprm(34);" />
<input type="button" id="36" value="n" onclick="imprm(35);" />
<input type="button" id="37" value="m" onclick="imprm(36);" />
<input type="button" id="38" value="," onclick="imprm(37);" />
<input type="button" id="39" value="." onclick="imprm(38);" />
<input type="button" id="40" value="-" onclick="imprm(39);" />
<input type="button" id="43" value="May&uacute;scula" onclick="muestra(capa2)" style="width:80px; height:20px; text-align:center; font-size:10px;" />
<br>
<input type="button" id="42" value="Espaciador" onclick="imprm(41);"  style="width:100px; height:20px; text-align:center; font-size:10px;" />
</div>
 
 
 
 
<div id="c2" class="posLay2">
<input type="button" id="1" value="!" onclick="imprM(0);" />
<input type="button" id="2" value=""" onclick="imprM(1);" />
<input type="button" id="3" value="&#183;" onclick="imprM(2);" />
<input type="button" id="4" value="&#37;" onclick="imprM(3);" />
<input type="button" id="5" value="/" onclick="imprM(4);" />
<input type="button" id="6" value="(" onclick="imprM(5);" />
<input type="button" id="7" value=")" onclick="imprM(6);" />
<input type="button" id="8" value="=" onclick="imprM(7);" />
<input type="button" id="9" value="?" onclick="imprM(8);" />
<input type="button" id="10" value="&#191;" onclick="imprM(9);" />
<input type="button" id="eliminar_caracter" value="Borrar" onclick="EliminarCaracter()" style="width:40px; height:20px; text-align:center; font-size:10px;" />
<br>
<input type="button" id="11" value="Q" onclick="imprM(10);" />
<input type="button" id="12" value="W" onclick="imprM(11);" />
<input type="button" id="13" value="E" onclick="imprM(12);" />
<input type="button" id="14" value="R" onclick="imprM(13);" />
<input type="button" id="15" value="T" onclick="imprM(14);" />
<input type="button" id="16" value="Y" onclick="imprM(15);" />
<input type="button" id="17" value="U" onclick="imprM(16);" />
<input type="button" id="18" value="I" onclick="imprM(17);" />
<input type="button" id="19" value="O" onclick="imprM(18);" />
<input type="button" id="20" value="P" onclick="imprM(19);" />
<input type="button" id="41" value="Salto" onclick="imprM(40);"  style="width:40px; height:20px; text-align:center; font-size:10px;" />
<br>
<input type="button" id="21" value="A" onclick="imprM(20);" />
<input type="button" id="22" value="S" onclick="imprM(21);" />
<input type="button" id="23" value="D" onclick="imprM(22);" />
<input type="button" id="24" value="F" onclick="imprM(23);" />
<input type="button" id="25" value="G" onclick="imprM(24);" />
<input type="button" id="26" value="H" onclick="imprM(25);" />
<input type="button" id="27" value="J" onclick="imprM(26);" />
<input type="button" id="28" value="K" onclick="imprM(27);" />
<input type="button" id="29" value="L" onclick="imprM(28);" />
<input type="button" id="30" value="&#209;" onclick="imprM(29);" />
<input type="button" id="44" value="Tab" onclick="imprM(42);" style="width:40px; height:20px; text-align:center; font-size:10px;" />
<br>
<input type="button" id="31" value="Z" onclick="imprM(30);" />
<input type="button" id="32" value="X" onclick="imprM(31);" />
<input type="button" id="33" value="C" onclick="imprM(32);" />
<input type="button" id="34" value="V" onclick="imprM(33);" />
<input type="button" id="35" value="B" onclick="imprM(34);" />
<input type="button" id="36" value="N" onclick="imprM(35);" />
<input type="button" id="37" value="M" onclick="imprM(36);" />
<input type="button" id="38" value=";" onclick="imprM(37);" />
<input type="button" id="39" value=":" onclick="imprM(38);" />
<input type="button" id="40" value="_" onclick="imprM(39);" />
<input type="button" id="43" value="May&uacute;scula" onclick="oculta(capa2)" style="width:80px; height:20px; text-align:center; font-size:10px;" />
<br>
<input type="button" id="42" value="Espaciador" onclick="imprM(41);"  style="width:100px; height:20px; text-align:center; font-size:10px;" />
</div>
 
<!-- FIN TECLADO -->
 
</body>
</html>