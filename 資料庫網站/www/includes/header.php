<div class="header">
    <div style="flex: 1;">
        <h3 style="margin: 0;">資工系師生入口網</h3>
    </div>
    <div style="display: flex; gap: 15px; align-items: center;">
        <?php if(isset($_SESSION['user_id'])): ?>
            <span style="font-weight: bold;">歡迎, <?php echo htmlspecialchars($_SESSION['username']); ?> 
            (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
            <a href="logout.php" class="btn" style="margin: 0; background: #dc3545;">登出</a>
        <?php else: ?>
            <a href="index.php?page=login" class="btn" style="margin: 0;">系統登入</a>
        <?php endif; ?>
    </div>
</div>
