<?php $exception = require('forum.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<!-- for Persona on IE -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Small, simple and lightweight forum system write in PHP" />
	<meta name="generator" content="Forum Five" />
	<meta name="author" content="">

	<title><?php if(!empty($topic)) print $topic['title'] . ' - '; ?>Forum Five</title>

	<!-- Bootstrap core CSS -->
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" />

	<!-- Custom styles for this template -->
	<style type="text/css">
	/* Space out content a bit */
	body {
		padding-top: 20px;
		padding-bottom: 20px;
	}

	img {
		max-width: 700px;
		max-height: 700px;
	}

	/* Everything but the jumbotron gets side spacing for mobile first views */
	.header,
	.marketing,
	.footer {
		padding-right: 15px;
		padding-left: 15px;
	}

	/* Custom page header */
	.header {
		border-bottom: 1px solid #e5e5e5;
	}
	/* Make the masthead heading the same height as the navigation */
	.header h3 {
		padding-bottom: 19px;
		margin-top: 0;
		margin-bottom: 0;
		line-height: 40px;
	}

	/* Custom page footer */
	.footer {
		padding-top: 19px;
		color: #777;
		border-top: 1px solid #e5e5e5;
	}

	/* Customize container */
	@media (min-width: 768px) {
		.container {
			max-width: 730px;
		}
	}
	.container-narrow > hr {
		margin: 30px 0;
	}

	/* Main marketing message and sign up button */
	.jumbotron {
		text-align: center;
		border-bottom: 1px solid #e5e5e5;
	}

	/* Supporting marketing content */
	.marketing {
		margin: 40px 0;
	}
	.marketing p + h4 {
		margin-top: 28px;
	}

	/* Responsive: Portrait tablets and up */
	@media screen and (min-width: 768px) {
		/* Remove the padding we set earlier */
		.header,
		.marketing,
		.footer {
			padding-right: 0;
			padding-left: 0;
		}
		/* Space out the masthead */
		.header {
			margin-bottom: 30px;
		}
		/* Remove the bottom border on the jumbotron for visual effect */
		.jumbotron {
			border-bottom: 0;
		}
	}
	</style>

	<?php if($_SESSION['email']) { ?>
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" />
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/summernote/0.6.0/summernote.css">
	<?php } ?>

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>

<body>

	<div class="container">
		<div class="header">
			<nav>
				<ul class="nav nav-pills pull-right">

					<li><a href="/">Home</a></li>
					<li><a href="/?t=1">FAQ</a></li>

					<?php if($_SESSION['email']) { ?>
						<li><a href="#" id="login_button">Change Email</a></li>
						<li><a href="#" id="logout_button">Logout</a></li>

					<?php } else { ?>
						<li><a href="#" id="login_button">Login/Register</a></li>
					<?php } ?>
				</ul>
			</nav>
			<h3 class="text-muted"><a href="/">Forum Five</a></h3>
		</div>

		<div class="marketing">

		<?php if(! $exception instanceof Exception) { ?>

			<?php if($email) { ?>

				<div class="jumbotron">

					<a href="http://www.gravatar.com/<?= md5($user['email']); ?>">
						<img src="http://www.gravatar.com/avatar/<?= md5($user['email']); ?>?s=80&r=g&d=mm" title="Gravatar.com Image">
					</a>

					<h2><?= $user['email']; ?></h2>
					<p class="lead">Joined <?= date('D, M jS Y H:i:s', $user['c']); ?></p>
					<p>User has <?= $user['posts']; ?> posts and has logged in <?= $user['logins']; ?> times</p>

					<?php if($_SESSION['admin']) { ?>
						<?php if($user['banned']) { ?>
							<a class="btn btn-success" href="?delete=unban&email=<?= $user['email']; ?>">Un-Ban User</a>
						<?php } else { ?>
							<a class="btn btn-danger" href="?delete=user&email=<?= $user['email']; ?>">Ban User</a>
						<?php } ?>
					<?php } ?>

				</div>

				<table class="table table-hover">

					<thead>
						<tr>
							<th>IP</th>
							<th>Comment</th>
							<th>Date</th>
						</tr>
					</thead>
					<tbody>

					<?php foreach($rows->fetchAll() as $row) { ?>
						<tr class="comments">
							<td>
								<a href="http://whatismyipaddress.com/ip/<?= $row['ip']; ?>" target="_blank"><?= $row['ip']; ?></a>
							</td>
							<td>
								<a href="/?topicID=<?= $row['topic_id']; ?>">view topic</a>							
							</td>
							<td>
								<?= date('D, M jS Y ga', $row['c']); ?><br>
								<a href="/?delete=comment&commentID=<?= $row['id']; ?>&email=<?= $user['email']; ?>&topicID=<?= $row['topic_id']; ?>">delete comment</a>
							</td>
							<td>
								<?= substr(strip_tags($row['body']), 0, 100); ?>
							</td>
						</tr>
					<?php } ?>

					</tbody>
				</table>



			<?php } elseif($topicID) { ?>

				<div class="media">
					<?php if($_SESSION['admin']) { ?>
						<a class="media-left" href="?email=<?= $topic['email'];?>">
							<img src="http://www.gravatar.com/avatar/<?= md5($topic['email']); ?>?s=40&r=g&d=mm" title="Gravatar.com Image">
						</a>
					<?php } else { ?>
						<a class="media-left" href="#">
							<img src="http://www.gravatar.com/avatar/<?= md5($topic['email']); ?>?s=40&r=g&d=mm" title="Gravatar.com Image">
						</a>
					<?php } ?>
					
					<div class="media-body">

						<h3 class="media-heading"><?= $topic['title']; ?></h4>

						<?php if($_SESSION['admin']) {?>
							<h5 class="media-heading"><?= $topic['email']; ?> -
							<a href="/?delete=topic&topicID=<?= $topic['id']; ?>">delete</a></h5>
						<?php } ?>

						<?= $topic['body']; ?>
						
					</div>
				</div>

				<hr>

				<div id="comments">
					<?php foreach($rows->fetchAll() as $row) { ?>

						<div class="media">

							<?php if($_SESSION['admin']) { ?>
								<a class="media-left" href="?email=<?= $topic['email'];?>">
									<img src="http://www.gravatar.com/avatar/<?= md5($row['email']); ?>?s=40&r=g&d=mm" title="Gravatar.com Image">
								</a>
							<?php } else { ?>
								<a class="media-left" href="#">
									<img src="http://www.gravatar.com/avatar/<?= md5($row['email']); ?>?s=40&r=g&d=mm" title="Gravatar.com Image">
								</a>
							<?php } ?>
							
							<div class="media-body">

								<?php if($_SESSION['admin']) {?>
									<h4 class="media-heading"><?= $row['email']; ?> - <a href="/?delete=comment&commentID=<?= $row['id']; ?>&topicID=<?= $topic['id']; ?>">delete</a></h4>
								<?php } ?>

								<?= $row['body']; ?>
								
							</div>
						</div>

						<hr>

					<?php } ?>
				</div>

				<?php if($_SESSION['email']) { ?>

					<form role="form" method="post">
						<legend>Leave a Reply</legend>
						<div class="form-group">
							<div class="summernote"></div>
							<textarea class="form-control" rows="7" name="body"></textarea>
						</div>
						<button type="submit" class="btn btn-default">Submit</button>
					</form>

				<?php } ?>

			<?php } else { ?>

				<div class="jumbotron">
					<h1>It's all just talk</h1>
					<p class="lead">This is a demonstration install of the <a href="https://github.com/Xeoncross/forumfive">ForumFive</a> PHP forum system. Please be respectful.</p>
				</div>

				<table class="table table-hover">

					<!--<caption>List of recent forum posts</caption>-->

					<thead>
						<tr>
							<th>User</th>
							<th>Topic</th>
							<th>Last Activity</th>
						</tr>
					</thead>
					<tbody>

					<?php foreach($rows->fetchAll() as $row) { ?>
						<tr class="topics">
							<td>
								<?php if($_SESSION['admin'] OR $_SESSION['email'] === $row['email']) { ?>
									<a href="?email=<?= $row['email'];?>">
										<img src="http://www.gravatar.com/avatar/<?php echo md5($row['email']); ?>?s=30&r=g&d=mm" class="img-polaroid" />
									</a>
								<?php } else { ?>
									<img src="http://www.gravatar.com/avatar/<?php echo md5($row['email']); ?>?s=30&r=g&d=mm" class="img-polaroid" />
								<?php } ?>
							</td>
							<td>
								<a href="?topicID=<?= $row['id']; ?>"><?= $row['title']; ?></a><br>


								<?php if($_SESSION['admin']) {?>
									<?= $row['email']; ?> - <a href="/?delete=topic&topicID=<?= $row['id']; ?>">delete</a>
								<?php } ?>
							</td>
							<td>
								<?= date('D, M jS Y ga', $row['u']); ?>
							</td>
						</tr>
					<?php } ?>

					</tbody>
				</table>

				<?php if($_SESSION['email']) { ?>

					<form role="form" method="post">
						<legend>Create new Topic</legend>
						<div class="form-group">
							<label for="title">Title</label>
							<input type="text" class="form-control" name="title">
						</div>
						<div class="form-group">
							<label for="body">Body</label>
							<div class="summernote"></div>
							<textarea class="form-control" rows="10" name="body"></textarea>
						</div>
						<button type="submit" class="btn btn-default">Submit</button>
					</form>

				<?php } ?>

			<?php } ?>

		<?php } elseif($exception instanceof Exception) { //var_dump($exception); //$e = $exception; //catch(Exception $e) { ?>

			<div id="exception">
				<?php if($exception->getMessage() == 'MISSING') { ?>
					Sorry, we could not find the topic
				<?php } elseif($exception->getMessage() == 'OFTEN') { ?>
					Sorry, you can only post twice every <?= ($_SESSION['trusted'] ? TRUSTED_WAIT : WAIT) / 60; ?> minutes. Please wait a few moments and then refresh the page to re-send your post.
				<?php } elseif($exception->getMessage() == 'REMOVED') { ?>
					The topic/comment has been removed.
				<?php } elseif($exception->getMessage() == 'EMAIL') { ?>
					Sorry, your email provider is banned because of spam accounts.
				<?php } elseif($exception->getMessage() == 'BANNED') { ?>
					Sorry, your account is banned. Remember the golden rule, "So whatever you wish that others would do to you, do also to them" - Matthew 7:12
				<?php } elseif($exception->getMessage() == 'LENGTH') { ?>
					Sorry, your topic or comment is to long.
				<?php } elseif($exception->getMessage() == 'REGISTER') { ?>
					Sorry, registration is currently disabled.
				<?php } elseif($exception->getMessage() == 'HEADER') { ?>
					You must have a topic header.
				<?php } ?>
			</div>

		<?php } ?>

		<?php //print '<pre>' . print_r($_SESSION, TRUE) . '</pre>'; ?>

		<br>

		<footer class="footer">
			
			<?php if($_SESSION['email']): ?>
				
				<p>Welcome <?= $_SESSION['email']; ?>, you have posted <?= $_SESSION['posts']; ?> messages.
				<?php if(TRUST_COUNT > $_SESSION['posts']) {
					print 'You will be a moderator after ' . (TRUST_COUNT - $_SESSION['posts']); ?> more posts.</p>
				<?php } ?>

			<?php elseif ( ! ALLOW_REGISTER): ?>
				<p>Registration is currently disabled. Existing users can still login.</p>

			<?php else: ?>
				<p>&copy; <?= date('Y'); ?> <?= htmlspecialchars(getenv('HTTP_HOST')); ?> - Powered by <a href="https://github.com/Xeoncross/forumfive">forumfive</a></p>
			<?php endif; ?>


		</footer>

	</div> <!-- /container -->


	<script src="https://login.persona.org/include.js"></script>

	<!-- include libraries(jQuery, bootstrap, fontawesome) -->
	<script type="text/javascript" src="//code.jquery.com/jquery-1.9.1.min.js"></script> 
	
	<?php if($_SESSION['email']) { ?>
		<!-- Summernote editor -->
		<script type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/summernote/0.5.2/summernote.min.js"></script>
		<script src="/editor.js"></script>
	<?php } ?>

	<script>
	$(function()
	{
		var currentUser = <?= $_SESSION['email'] ? "'". $_SESSION['email'] . "'" : 'null'; ?>;

		navigator.id.watch({
			loggedInUser: currentUser,
			onlogin: function(assertion)
			{
				$.ajax({
					type: 'POST',
					data: { a: assertion },
					success: function(res, status, xhr)
					{
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
				document.cookie = "<?= session_name(); ?>=; expires="+date.toGMTString()+"; path=/";
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

	if (!('contains' in Array.prototype)) {
		Array.prototype.contains = function(arr, startIndex) {
			return ''.indexOf.call(this, arr, startIndex) !== -1;
		};
	}
	</script>

</body>
</html>
