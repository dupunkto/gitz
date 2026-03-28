<header class="epic">
  <div class="container">
    <h1>{du}punkto git hosting</h1>
  </div>
</header>

<picture>
  <source srcset="/api/graph?m=light" media="(prefers-color-scheme: light)"/>
  <source srcset="/api/graph?m=dark" media="(prefers-color-scheme: dark)"/>

  <img
    src="/api/graph"
    width="100%"
    class="container commit-graph"
    alt="Loading commit graph... (turns out analyzing 'git log' for 70+ repos is quite expensive)"
  />
</picture>

<?php $total_repos = 0 ?>
<?php $total_size = 0 ?>

<!-- I'm not proud of this mess..., but it works :D -->
<main class="container listing">
  <?php foreach(NAMESPACES as $namespace): ?>
    <?php $count = 0 ?>
    <section>
      <h2><?= $namespace ?></h2>
      
      <ul>
        <?php foreach(\core\listRepositories($git, $namespace, detailed: true) as $details): ?>
          <?php
            $path = path_join(SCAN_PATH, $namespace, $details['name']);
            $repo = $git->open($path);
            
            $total_repos++;
            $total_size += \core\getTotalSize($repo);
            $description = \core\getDescription($repo);

            $count++;
          ?>

          <?php if($count == MAX_REPOS + 1) echo "</ul><details><summary>More</summary>" ?>

          <?php if($count <= MAX_REPOS) echo "<li>" ?>
            <a href="/~<?= $namespace ?>/<?= $details['name'] ?>">
              <?php if($details['recent']): ?>
                <time class="dt"><?= \dates\timeAgo($details['updated']) ?></time>
              <?php endif; ?>
              <h3><?= $details['name'] ?></h3>
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