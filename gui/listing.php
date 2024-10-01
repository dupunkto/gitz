<header class="epic">
  <div class="container">
    <h1>{du}punkto git hosting</h1>
  </div>
</header>

<img src="/api/graph" width="100%" class="container" style="margin: 1em auto">

<?php $total_repos = 0 ?>
<?php $total_size = 0 ?>

<!-- I'm not proud of this mess..., but it works :D -->
<main class="container listing">
  <?php foreach(NAMESPACES as $namespace): ?>
    <?php $count = 0 ?>
    <section>
      <h2><?= $namespace ?></h2>
      
      <ul>
        <?php foreach(\core\listRepositories($git, $namespace) as $repo_name): ?>
          <?php
            $path = path_join(SCAN_PATH, $namespace, $repo_name);
            $repo = $git->open($path);
            
            $total_repos++;
            $total_size += \core\getTotalSize($repo);
            $description = \core\getDescription($repo);

            $count++;
          ?>

          <?php if($count == MAX_REPOS + 1) echo "</ul><details><summary>More</summary>" ?>

          <?php if($count <= MAX_REPOS) echo "<li>" ?>
            <a href="/~<?= $namespace ?>/<?= $repo_name ?>">
              <h3><?= $repo_name ?></h3>
              <p><?= \core\getDescription($repo) ?></p>
            </a>
          <?php if($count <= MAX_REPOS) echo "</li>" ?>
        <?php endforeach; ?>
      </details>
    </section>
  <?php endforeach; ?>
</main>

<footer class="container">
  <span><?= $total_repos ?> repos, totalling <?= \core\formatSize($total_size) ?></span>

  <span>
    Powered by <a href="//git.dupunkto.org/dupunkto/gitz">Gitz</a>, 
    a <a href="//dupunkto.org">{du}punkto</a> project.
  </span>
</footer>