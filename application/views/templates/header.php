<!DOCTYPE html>
<html>
<!-- Page header -->
 <head>
  <!-- meta infos -->
  <meta charset="UTF-8">
  <title><?php echo $page_title; ?></title>
  <meta name="author" content="Philip Schneider & Jason Wijegooneratne">
  <meta name="description" content="<?php echo $page_description; ?>">
  <!-- stylesheets and links -->
  <link rel="shortcut icon" href="favicon.ico" type="image/vnd.microsoft.icon" />
  <link href='http://fonts.googleapis.com/css?family=Merriweather+Sans' rel='stylesheet' type='text/css' />
  <!-- Bootstrap -->
  <link href="<?php echo base_url(); ?>resources/bootstrap/less/bootstrap.less" rel="stylesheet" type="text/less" media="screen" />
  <link href="<?php echo base_url(); ?>resources/bootstrap/less/responsive.less" rel="stylesheet" type="text/less" media="screen" />
  <!-- Google Analytics Tracking Code. Uses the Universal Engine -->
  <script type="text/javascript">
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
    ga('create', 'UA-33140721-3', '54.251.251.190');
    ga('send', 'pageview');
  </script>
 </head>
 <!-- Begin page content -->
 <body>
  <div class='container'>
    <!-- some initial related heading stuff -->
    <h1 class='brand'>LinkedIn Data Miner</h1>
    <p class='lead'>Welcome to the LinkedIn Data Mining application!</p>
    <!-- Dynamic content begins here -->
