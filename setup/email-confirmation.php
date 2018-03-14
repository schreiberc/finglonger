<!doctype html>
<?php $id = $_GET['token']; ?>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" href="apple-touch-icon.png">
        <!-- Place favicon.ico in the root directory -->

        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/main_new.css">
    </head>
    <body>
    <div id="container">
        <!--[if lt IE 9]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

        <!-- Add your site or application content here -->
        <div id="install">
        </div>
        <div id="installing">
            <img src="../assets/img/logo_2.png">
            <h1>Thanks for confirming<br/>your email.</h1>            
        </div>
        <div id="error">
            
        </div>
                          
    </div>
    <script src="js/vendor/jquery-1.11.3.min.js"></script>
    <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.3.min.js"><\/script>')</script>
    <script type="text/javascript">
        var token = '<?php echo $id;?>';
    </script>
    <script src="js/email_conf.js"></script>
        
    </body>
</html>
