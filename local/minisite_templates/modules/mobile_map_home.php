<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MobileMapHomeModule';

class MobileMapHomeModule extends DefaultMinisiteModule {
    function init( $args = array() ) {

    }

    function has_content() {
        return true;
    }

    function run() {
        ?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <style type="text/css">
        #wrapper {
            text-align:left;
            margin:0 auto;
        }
        #wifi img{
            z-index: 1;
        }

        #link { color: blue; cursor:pointer; }

        #link:hover {
            color: black;
            background-color: #def ;
        }
        .containerdiv { float: left; position: relative; }

        .cornerimage { position: absolute; top: 0; left: 0; }

    </style>
    <script type="text/javascript">

        function buildingReset() {
            document.getElementById("buildings").selectedIndex = document.getElementById("buildings").getAttribute("default");
        }

        function departmentReset() {
            document.getElementById("departments").selectedIndex = document.getElementById("departments").getAttribute("default");
        }

        function switchMenu(obj) {
            hideAll();
            var o = document.getElementById(obj);
            o.style.display = '';
        }
        function show(obj) {
            var o = document.getElementById(obj);
            o.style.display = '';
        }
        function hide(obj) {
            var o = document.getElementById(obj);
            o.style.display = 'none';
        }

        function WinOpen1() {
            var url=document.redirect1.buildings.value
            document.location.href=url
        }

        function WinOpen2() {
            var url=document.redirect2.departments.value
            document.location.href=url
        }

    </script>
</head>

<body onload="buildingReset();departmentReset()">

<th>Buildings:</th>
<form name="redirect1">
    <select id="buildings" name="buildings" onchange="departmentReset()">
        <option value=" " selected></option>
        <option value="olin/">Olin</option>
        <option value="koren/">Koren</option>
        <option value="sampson/">Sampson Hoffland</option>
    </select>
    <input type=button value="Go" onClick="WinOpen1();">
</form>
<p></p>
<th>Departments:</th>
<form name="redirect2">
    <select id="departments" name="departments" onchange="buildingReset()">
        <option value="blank" selected></option>
        <option value="koren/">Africana Studies</option>
        <option value="koren/">Anthropology</option>
        <option value="koren/">Archaeology</option>
        <option value="sampson/">Biology</option>
        <option value="olin/">Business</option>
        <option value="sampson/">Chemistry</option>
        <option value="olin/">Computer Science</option>
        <option value="olin/">Economics</option>
        <option value="koren/">Education</option>
        <option value="olin/">Math</option>
        <option value="koren/">Political Science</option>
        <option value="koren/">Sociology</option>
        <option value="koren/">Social Work</option>
        <option value="koren/">Women's and Gender Studies</option>
    </select>
    <input type=button value="Go" onClick="WinOpen2();">
</form>

</body>
        <?php
    }
}
?>