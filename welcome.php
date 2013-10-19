
<html>
<head>
	
	<title>Welcome</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="css/bootstrap-responsive.min.css" rel="stylesheet" type="text/css" />
	<link href="css/custom.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript"> </script>
	<?php require_once 'includes/global.inc.php';
?>
</head>	
<body>
	
<?php if(isset($_SESSION['logged_in'])) : ?>
    <?php $user = unserialize($_SESSION['user']); ?>
    Hello, <?php echo $user->username; ?>. You are logged in. <a href="logout.php">Logout</a> | <a href="settings.php">Change Email</a>
<?php else : ?>
    You are not logged in. <a href="login.php">Log In</a> | <a href="register.php">Register</a>
<?php endif; ?>

	<div class="container">
      <div class="navbar-header">
        <button data-target=".navbar-responsive-collapse" data-toggle="collapse" class="navbar-toggle" type="button">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
           <a href="welcome.html" class="navbar-brand active"><img src="img/logoSmallWhite.gif"></a>
      </div>
         <div class="navbar-collapse collapse navbar-inverse-collapse">
           <ul class="nav navbar-nav">
             <li><a href="study.html">Study</a></li>
             <li><a href="knowledge.html">Knowledge</a></li>
             <li><a href="Leader.html">Group Leader</a></li>
             <li><a href="forum/index.php">Forum</a></li>
             
           </ul>
       <!--  <form class="navbar-form navbar-right">
          <input type="text" placeholder="Search" class="form-control col-lg-8">
        </form> -->
       
         </div>	<!-- /.nav-collapse -->
       </div>
<div class="container">
    <div class="page-header" >
					<h1 align="right"> God is in the room</h1>
						<h3>This page is under construction, please check back frequently to observe the progress. </h3>					
					</div>			
					</div>
</div>
	<div class="row-fluid">	
		<div class="span1"></div>
	 

		<div class="span7">
			<div class="well well-lg" style="background: url(img/lightTreeTrunks.png)">
				<font color="222222"> <h2 >
					This is <b>not</b> your little sisters Sunday School.  
					<br>
					<br>
					We are <b>not</b> going to dumb things down, or make things cute and sweet.
					<br>
					<br>
					This is <b>not</b> for a lecture class, it's for discussion. . . so discuss already!   
					cause <em>you do have something to add.</em>
					<br>
					<br>
					This is for seekers and doubters and those who struggle with faith or just don't have any.
					<br>
					<br>
					This is for those who hate Sunday School
					<br>
					<br>
					Those who would puke if they saw one more boat with an old bearded man and a giraffe on it.
					<br>
					<br>
					This is more for those who are "On a boat" with Lonely Island.
				</h2></font>
			</div>
		</div>

		<div class="span3">
		 	<div class="panel panel-default">
		 		<div class="panel-body">
				 	<p class="lead">
						Disclaimer...... <br>if you are easily offended, <br> <b>do not</b> <br> enter this site....<br><em> there is real life junk inside here.
					</p>
				</div>
				<div class="panel-footer">
			      <a class="btn btn-block btn-danger" href="knowledge.html" > Enter Site </a>       
		      </div>    
				<div class="panel-footer">
			      <a class="btn btn-block btn-primary" href="study.html" > This Weeks Lesson Sheet </a>       
		      </div>    
     			<div class="panel-footer">
			      <a class="btn btn-block btn-warning" href="Leader.html" > Group Leader </a>       
		      </div> 
	     </div>
     </div> 
<div class="span12">
	<p>
		This site is created by Christy Watson in cooperation with ..... . 
	</p>
</div>
</body>
</html>
