<?php
include(dirname(dirname(__FILE__)) . '/snserver/session_functions.php');
if (!check_logged_in()) {
    header("Location: /");
    exit(1);
}

if($_POST) {

    $errorMessages = array();
    $newPass = null;

    if (!$_POST["new"] || strlen($_POST["new"]) === 0) {
        $errorMessages[] = "New password not specified";
    }

    if (!$_POST["repeat"] || strlen($_POST["repeat"]) === 0) {
        $errorMessages[] = "Repeat password not specified";
    }

    if ($_POST["new"] !== $_POST["repeat"]) {
        $errorMessages[] = "New And repeat password doesn't match";
    }

    if (count($errorMessages) === 0) {
        // No error
        $newPass = $_POST["new"];
        $cmd = "su -l root -c 'echo \"buurst:$newPass\" | chpasswd'";
        $result = sudo_execute($cmd);

        $passChanged = false;

        if ($result && $result["rv"] === 0) {
            $passChanged = true;
        }
    }
}

?>
<html>
<head>
    <title>Change pass</title>
    <style>
        head {
            color: #333333;
        }

        body {
            color: #333333;
        }

        table {
            border-width: 0px;
            empty-cells: hide;
        }

        table.formsection, table.sortable, table.ui_table, table.loginform {
            border-collapse: collapse;
            border: 1px solid #FFFFFF;
            width: 100%;
        }

        img, a img {
            border: 0;
        }

        tr.row0 {
            background-color: #e8e8ea;
        }

        tr.row1 {
            background-color: #f8f8fa;
        }

        table.formsection thead, table.sortable thead, table.ui_table thead, table.loginform thead {
            background-color: #427ad1;
            border: 0px;
            color: #ffffff;
            border: 2px solid #b3b6b0;
        }

        table.formsection tbody, table.sortable tbody, table.ui_table tbody, table.loginform tbody {
            background-color: #EFEFEF;
        }

        tr.maintitle {
            color: #ffffff;
            background-color: #427ad1;
        }

        td.maintitle {
            color: #ffffff;
            background-color: #427ad1;
        }

        tr.maintitle a, tr.maintitle a:visited {
            color: #ffffff;
        }

        td.maintitle a, td.maintitle a:visited {
            color: #ffffff;
        }

        tr.maintitle a:hover {
            color: #EFEFEF;
        }

        td.maintitle a:hover {
            color: #EFEFEF;
        }

        a:link {
            color: #333399;
            text-decoration: none;
        }

        a:hover, a:visited:hover {
            color: #6666EE;
            text-decoration: none;
        }

        a:visited {
            color: #333399;
            text-decoration: none;
        }

        body, p, td, br, center {
            font-size: 10pt;
            font-family: sans-serif;
        }

        title {
            color: #333333;
            font-family: sans-serif;
        }

        h1 {
            color: #333333;
            font-size: 150%;
            font-family: sans-serif;
        }

        h2 {
            color: #333333;
            font-size: 130%;
            font-family: sans-serif;
        }

        h3 {
            color: #333333;
            font-size: 125%;
            font-family: sans-serif;
        }

        h4 {
            color: #333333;
            font-size: 120%;
            font-family: sans-serif;
        }

        th {
            font-size: small;
        }

        pre {
            font-size: 8pt;
        }

        #main {
            border-style: solid;
            border: 1px solid #FFFFFF;
            margin: 0;
            padding: 0;
        }

        tr.mainsel {
            background-color: #ddffbb;
        }

        tr.mainhigh {
            background-color: #ffffbb;
        }

        tr.mainhighsel {
            background-color: #bbffcc;
        }

        .itemhidden {
            display: none;
        }

        .itemshown {
            display: block;
        }

        .barchart {
            padding: 1px;
            border: 1px solid #b3b6b0;
            position: relative;
        }

        .ui_post_header {
            font-size: 120%;
            text-align: center;
            padding: 4px;
        }

        hr {
            border: 0;
            width: 90%;
            height: 1px;
            color: #D9D9D9;
            background-color: #D9D9D9;
        }

        table.wrapper {
            background-color: #D9D9D9;
            border: 0;
            padding: 0;
            margin: 0;
            border-collapse: collapse;
        }

        div.wrapper {
            border: 1px solid #D9D9D9;
            background-color: #F5F5F5;
            padding: 0;
            margin: 0;
        }

        .shrinkwrapper {
            background-color: #D9D9D9;
            border: 0;
            padding: 0;
            margin: 0;
            border-collapse: collapse;
        }

        .tabSelected {
            background-color: #D9D9D9;
        }

        .tabUnselected {
            background-color: #dadaf8;
        }
    </style>
</head>
<body bgcolor=#ffffff link=#0000ee vlink=#0000ee text=#000000>
<table class='header' width=100%>
    <tr>
        <td id='headln2c' align=center width=70%><font size=+2>Change Password</font></td>
        <td id='headln2r' width=15% valign=top align=right></td>
    </tr>
</table>
<p>
<form class='ui_form' method='post'>
    <table class='shrinkwrapper'>
        <tr>
            <td>
                <table class='ui_table'>
                    <tbody>
                    <tr class='ui_table_body'>
                        <td colspan=1>
                            <table width=100%>
                                <tr class='ui_table_row'>
                                    <td valign=top class='ui_label'><b>Changing password for</b></td>
                                    <td valign=top colspan=1 class='ui_value'>buurst</td>
                                </tr>
                                <?php
                                    if ($_POST && count($errorMessages) === 0) {
                                        if ($passChanged) {
                                            echo "Password changed successfully.";
                                        }
                                        else {
                                            echo "Unable to change password, check logs for more info.";
                                        }
                                    }
                                ?>
                                <tr class='ui_table_row'>
                                    <td valign=top class='ui_label'><b><label for="new">New password</label></b></td>
                                    <td valign=top colspan=1 class='ui_value'><input class='ui_password' type='password'
                                                                                     name="new" id="new" size=30></td>
                                </tr>
                                <tr class='ui_table_row'>
                                    <td valign=top class='ui_label'><b><label for="repeat">New password (again)</label></b>
                                    </td>
                                    <td valign=top colspan=1 class='ui_value'><input class='ui_password' type='password'
                                                                                     name="repeat" id="repeat" size=30>
                                    </td>
                                </tr>
                                <?php
                                    if (count($errorMessages)) {
                                        ?>
                                        <tr><td>
                                        <p>Please fix the errors</p>
                                <?php
                                        foreach ($errorMessages as $errorMessage) {
                                            echo "<p>$errorMessage</p>";
                                        }
                                        ?>
                                    </td>
                                    </tr>
                                <?php
                                    }

                                ?>
                                </tbody></table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table class='ui_form_end_buttons'>
        <tr>
            <td><input class='ui_submit' type='submit' value="Change">
            </td>
        </tr>
    </table>
</form>
</div>
</body>

</html>
