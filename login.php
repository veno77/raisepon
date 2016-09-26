<?php
header('Content-Type: text/html; charset=utf-8');
print "<link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\">";
print "<center><img src=\"pic/logo.png\">";
?>
<body><center>
<h2>Login Here</h2>
<form action="login_submit.php" method="post">
<p>
<label for="username">Username</label>
<input type="text" id="username" name="username" value="" maxlength="20" />
</p>
<p>
<label for="password">Password </label>
<input type="password" id="password" name="password" value="" maxlength="20" />
</p>
<p>
<input type="submit" value="Login" />
</p>
</form>
</body>
</html>