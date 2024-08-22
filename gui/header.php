<header>
  <div class="container">
    <h1><a href="/">~<?= $namespace ?></a>/<?= $repo_name ?></h1>
    <nav>
      <a href="#" <?php if($page == "summary") echo 'class="selected"' ?>>Summary</a>
      <?php foreach(\core\listRemotes($repo) as $remote): ?>
        <a href="<?= \core\getRemoteURL($repo, $remote) ?>"><?= $remote ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
  <div class="line">
    <p class="container description"><?= \core\getDescription($namespace, $repo_name) ?></p>
  </div>
</header>