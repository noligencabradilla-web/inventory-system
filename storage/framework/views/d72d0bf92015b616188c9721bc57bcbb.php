<?php
  $brand = 'Inventory System';
  $pageTitle = 'Notifications';
?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('client.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <h2>Notifications</h2>
    
    <div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
        <p style="color:var(--muted); margin:0;">
            Stay updated on your requests, inventory status, and team activity.
        </p>
        <button onclick="markAllAsRead()" style="padding:8px 16px; background:var(--blue); color:white; border:none; border-radius:6px; cursor:pointer; font-size:14px;">
            Mark All as Read
        </button>
    </div>

    <?php if($notifications->isEmpty()): ?>
        <div style="text-align:center; padding:60px 20px; color:var(--muted);">
            <svg style="width:48px; height:48px; margin-bottom:16px; opacity:0.3;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <h3 style="margin:0 0 8px 0;">No notifications</h3>
            <p style="margin:0;">You're all caught up! Check back later for updates.</p>
        </div>
    <?php else: ?>
        <div style="display:grid; gap:12px;">
            <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="notification-item <?php echo e(!$notification->read ? 'unread' : ''); ?>" 
                     style="display:flex; align-items:flex-start; gap:12px; padding:16px; background:var(--panel1); border:1px solid var(--border); border-radius:8px; transition:all 0.2s ease; cursor:pointer;"
                     onclick="markAsRead('<?php echo e($notification->id); ?>')">
                    
                    <div class="notification-icon" style="width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:<?php echo e($notification->color === 'green' ? 'var(--green-light)' : ($notification->color === 'red' ? 'var(--red-light)' : ($notification->color === 'orange' ? 'var(--orange-light)' : ($notification->color === 'yellow' ? 'var(--yellow-light)' : 'var(--blue-light)')))); ?>; color:<?php echo e($notification->color === 'green' ? 'var(--green)' : ($notification->color === 'red' ? 'var(--red)' : ($notification->color === 'orange' ? 'var(--orange)' : ($notification->color === 'yellow' ? 'var(--yellow)' : 'var(--blue)')))); ?>;">
                        <?php switch($notification->icon):
                            case ('clock'): ?>
                                <svg style="width:20px; height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?php break; ?>
                            <?php case ('check-circle'): ?>
                                <svg style="width:20px; height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <?php break; ?>
                            <?php case ('x-circle'): ?>
                                <svg style="width:20px; height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                </svg>
                                <?php break; ?>
                            <?php case ('alert-triangle'): ?>
                                <svg style="width:20px; height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                                <?php break; ?>
                            <?php case ('user-plus'): ?>
                                <svg style="width:20px; height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                </svg>
                                <?php break; ?>
                            <?php case ('package'): ?>
                                <svg style="width:20px; height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                                <?php break; ?>
                            <?php default: ?>
                                <svg style="width:20px; height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                        <?php endswitch; ?>
                    </div>
                    
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                            <h4 style="margin:0; font-size:16px; color:var(--text);"><?php echo e($notification->title); ?></h4>
                            <span style="font-size:12px; color:var(--muted); white-space:nowrap;"><?php echo e($notification->created_at->diffForHumans()); ?></span>
                        </div>
                        <p style="margin:0 0 8px 0; color:var(--muted); font-size:14px; line-height:1.4;"><?php echo e($notification->message); ?></p>
                        <?php if($notification->action_url): ?>
                            <a href="<?php echo e($notification->action_url); ?>" style="display:inline-flex; align-items:center; gap:4px; color:var(--blue); text-decoration:none; font-size:14px; font-weight:500;">
                                View Details
                                <svg style="width:14px; height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(!$notification->read): ?>
                        <div style="width:8px; height:8px; background:var(--blue); border-radius:50%; flex-shrink:0;"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <style>
    .notification-item:hover {
        background: var(--panel2);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .notification-item.unread {
        border-left: 4px solid var(--blue);
        background: var(--blue-light);
    }
    
    .notification-item.unread .notification-icon {
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    }
    </style>

    <script>
    function markAsRead(id) {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const formData = new FormData();
        formData.append('_token', token);
        
        fetch(`/client/notifications/${id}/read`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const element = document.querySelector(`[onclick="markAsRead('${id}')"]`);
                if (element) {
                    element.classList.remove('unread');
                    const unreadDot = element.querySelector('[style*="background:var(--blue)"]');
                    if (unreadDot) unreadDot.remove();
                }
                updateNotificationCount();
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }
    
    function markAllAsRead() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const formData = new FormData();
        formData.append('_token', token);
        
        fetch('/client/notifications/read-all', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                    const unreadDot = item.querySelector('[style*="background:var(--blue)"]');
                    if (unreadDot) unreadDot.remove();
                });
                updateNotificationCount();
            }
        })
        .catch(error => console.error('Error marking all notifications as read:', error));
    }
    
    function updateNotificationCount() {
        fetch('/client/notifications/counts')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('client-notification-badge');
                if (badge) {
                    if (data.total > 0) {
                        badge.style.display = 'inline-block';
                        badge.textContent = data.total;
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error updating notification count:', error));
    }
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\inventory-system\resources\views/client/notifications.blade.php ENDPATH**/ ?>