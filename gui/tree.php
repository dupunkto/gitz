<div class="container tree">
  <ul>
    <?php if(!in_array($request_path, ["", "."])): ?>
      <li>
        <code class="mode">d---------</code>
        <a href="<?= $repo_url ?>/tree/<?= $params[1] ?>/<?= path_parent($request_path) ?>">
         ..
        </a>
      </li>
    <?php endif; ?>
    <?php foreach(\core\getTree($repo, $request_path, $hash) as $child): ?>    
      <li>
        <code class="mode"><?= \core\fmtMode($child['mode']) ?></code>
        <a href="<?= $repo_url ?>/<?= $child['type'] ?>/<?= $params[1] ?>/<?= path_join($request_path, $child['path']) ?>">
          <?= $child['path'] ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
