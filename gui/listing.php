<header class="epic">
  <div class="container">
    <h1>{du}punkto git hosting</h1>
  </div>
</header>

<!-- I'm not proud of this mess..., but it works :D -->
<main class="container listing">
    <?php foreach(NAMESPACES as $namespace): ?>
      <?php $count = 0 ?>
      <section>
        <h2><?= $namespace ?></h2>
        
        <ul>
          <?php foreach(\core\listRepositories($git, $namespace) as $repo): ?>
            <?php $count++ ?>
            <?php if($count == 8) echo "</ul><details><summary>More</summary>" ?>
            <?php if($count < 8) echo "<li>" ?>
            <a href="/~<?= $namespace ?>/<?= $repo ?>">
              <h3><?= $repo ?></h3>
              <p><?= \core\getDescription($namespace, $repo) ?></p>
            </a>
            <?php if($count < 8) echo "</li>" ?>
          <?php endforeach; ?>
        </details>
      </section>
    <?php endforeach; ?>
</main>