<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Forum Five</title>

	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Small, lightweight forum system">
	<meta name="author" content="">

	<link href="bootstrap/css/bootstrap.css" rel="stylesheet">

	<style type="text/css">
		body {
			padding-top: 20px;
			padding-bottom: 40px;
		}

		/* Custom container */
		.container-narrow {
			margin: 0 auto;
			max-width: 700px;
		}
		.container-narrow > hr {
			margin: 30px 0;
		}

		/* Main marketing message and sign up button */
		.jumbotron {
			margin: 60px 0;
			text-align: center;
		}
		.jumbotron h1 {
			font-size: 72px;
			line-height: 1;
		}
		.jumbotron .btn {
			font-size: 21px;
			padding: 14px 24px;
		}

		/* Supporting marketing content */
		.marketing {
			margin: 60px 0;
		}
		.marketing p + h4 {
			margin-top: 28px;
		}
	</style>

	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">

	<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

</head>
<body>

	<div class="container-narrow">

		<?php $exception = require('forum.php'); ?>
		
		<div class="masthead">
			<ul class="nav nav-pills pull-right">
				<li><a href="/">Home</a></li>
				<li><a href="/?t=1">FAQ</a></li>

				<li>
					<?php if($_SESSION['email']) { ?>
						<!-- Welcome, <?php print $_SESSION['email']; ?>-->
						<a href="#" id="logout_button">Logout</a>
					<?php } else { ?>
						<a href="#" id="login_button">Login</a>
						<!-- <img src="https://browserid.org/i/sign_in_green.png" alt="Sign-in Button">-->
					<?php } ?>
				</li>
			</ul>

			<h3 class="muted">Forum Five</h3>
		</div>

		<hr>

		<div class="marketing">
			
		<?php if(! $exception instanceof \Exception) { ?>

			<?php if($t) { ?>

				<h3><?php print $o['h']; ?></h3>

				<p><?php print $o['b']; ?></p>

				<?php if($_SESSION['admin']) {?>
					<?php print $o['e']; ?> - <a class="btn btn-danger" href="/?d=t&t=<?php print $o['i']; ?>">delete</a>
				<?php } ?>

				<hr>
				
				<div id="comments">
					<?php foreach($rows->fetchAll() as $row) { ?>

						<div class="clearfix content-heading comment">
							<img class="pull-left img-polaroid" src="http://www.gravatar.com/avatar/<?php echo md5($row['e']); ?>?s=40&r=g&d=mm" style="margin-right: .7em;" />
							
							<p><?php print $row['b']; ?></p>
							
							<?php if($_SESSION['admin']) {?>
								<p><?php print $row['e']; ?> - <a href="/?d=c&c=<?php print $row['i']; ?>&t=<?php print $o['i']; ?>">delete</a></p>
							<?php } ?>
						</div>

					<?php } ?>
				</div>

				<?php if($_SESSION['email']) { ?>
					<form method="post">
						<fieldset>
							<legend>Leave a reply</legend>

							<textarea name="b" style="width: 100%; min-height: 150px;"></textarea>
							
							<input type="submit" class="btn btn-primary" value="Submit" />
						</fieldset>
					</form>
				<?php } ?>

			<?php } else { ?>
				
				<div class="jumbotron">
					<h1>Super awesome marketing speak!</h1>
					<p class="lead">Cras justo odio, dapibus ac facilisis in, egestas eget quam. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</p>
				</div>

				<table class="table table-hover">

					<!--<caption>List of recent forum posts</caption>-->

					<thead>
						<tr>
							<th>User</th>
							<th>Topic</th>
						</tr>
					</thead>
					<tbody>

					<?php foreach($rows->fetchAll() as $row) { ?>
						<tr class="topics">
							<td>
								<img src="http://www.gravatar.com/avatar/<?php echo md5($row['e']); ?>?s=30&r=g&d=mm" class="img-polaroid" />
							</td>
							<td>
								<a href="?t=<?php print $row['i']; ?>"><?php print $row['h']; ?></a><br>
								
								
								<?php if($_SESSION['admin']) {?>
									<?php print $row['e']; ?> - <a href="/?d=t&t=<?php print $row['i']; ?>">delete</a>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>

					</tbody>
				</table>

				<?php if($_SESSION['email']) { ?>
					<form method="post">
						<fieldset>
							<legend>Create new Topic</legend>

							<label>Topic Title</label>
							<input type="text" name="h" />
							
							<label>Topic Text</label>
							<textarea name="b" style="width: 100%; min-height: 150px;"></textarea>
							
							<input type="submit" class="btn btn-primary" value="Submit" />
						</fieldset>
					</form>
				<?php } ?>

			<?php } ?>

		<?php } elseif($exception instanceof \Exception) { //var_dump($exception); //$e = $exception; //catch(Exception $e) { ?>

			<div id="exception">
				<?php if($exception->getMessage() == 'MISSING') { ?>
					Sorry, we could not find the topic
				<?php } elseif($exception->getMessage() == 'OFTEN') { ?>
					Sorry, you can only post twice every <?php print WAIT / 60; ?> minutes. Please wait a few minutes.
				<?php } elseif($exception->getMessage() == 'REMOVED') { ?>
					The topic/comment has been removed.
				<?php } elseif($exception->getMessage() == 'HEADER') { ?>
					You must have a topic header
				<?php } ?>
			</div>

		<?php } ?>

		<?php //print '<pre>' . print_r($_SESSION, TRUE) . '</pre>'; ?>

		</div>

		<hr>

		<div class="footer">
			<p>&copy; <?php print date('Y'); ?> <?php print htmlspecialchars(getenv('HTTP_HOST')); ?> - <?php print $_SESSION['email']; ?></p>
		</div>

	</div>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script src="https://login.persona.org/include.js"></script>
	<script src="/bootstrap/js/bootstrap.min.js"></script>

	<script>
	$(function()
	{
		var currentUser = <?php print $_SESSION['email'] ? "'". $_SESSION['email'] . "'" : 'null'; ?>;

		navigator.id.watch({
			loggedInUser: currentUser,
			onlogin: function(assertion)
			{
				$.ajax({
					type: 'POST',
					data: { a: assertion },
					success: function(res, status, xhr)
					{
						//console.log(res);
						window.location.href = window.location.href;
					},
					error: function(xhr, status, err)
					{
						alert("Login failure: " + err);
					}
				});
			},
			onlogout: function()
			{
				// Delete the session cookie
				var date = new Date();
				document.cookie = "<?php print session_name(); ?>=; expires="+date.toGMTString()+"; path=/";
				window.location.href = window.location.href;
			}
		});

		$('#login_button').click(function(e)
		{
			e.preventDefault();
			navigator.id.request();
			return false;
		});

		$('#logout_button').click(function(e)
		{
			e.preventDefault();
			navigator.id.logout();
			return false;
		});

	});
	</script>

</body>
</html>