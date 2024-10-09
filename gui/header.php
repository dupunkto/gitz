<header>
  <div class="container">
    <h1><a href="/">~<?= $namespace ?></a>/<?= $repo_name ?></h1>
    <nav>
      <a href="#" <?php if($page == "summary") echo 'class="selected"' ?>>Summary</a>
      <?php foreach(\core\listRemotes($repo) as $remote): ?>
        <a href="<?= \core\getRemoteURL($repo, $remote) ?>"><?= $remote ?></a>
      <?php endforeach; ?>
      <?php if(has_tld($repo_name)): ?>
        <a href="//<?= $repo_name ?>">Homepage</a>
      <?php endif; ?>
    </nav>
  </div>
  <div class="line">
    <p class="container description"><?= \core\getDescription($repo) ?></p>
  </div>
</header>