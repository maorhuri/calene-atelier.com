<?php
/**
 * Admin Pending Dealers Page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get pending dealers
$args = array(
    'role' => 'dealer',
    'meta_query' => array(
        array(
            'key' => '_dealer_approved',
            'value' => 'pending',
            'compare' => '='
        )
    ),
    'orderby' => 'registered',
    'order' => 'DESC'
);

$pending_dealers = get_users($args);

// Get all dealers for tabs
$all_dealers_args = array(
    'role' => 'dealer',
    'orderby' => 'registered',
    'order' => 'DESC'
);
$all_dealers = get_users($all_dealers_args);

$approved_count = 0;
$pending_count = 0;
$rejected_count = 0;

foreach ($all_dealers as $dealer) {
    $status = get_user_meta($dealer->ID, '_dealer_approved', true);
    if ($status === 'yes') $approved_count++;
    elseif ($status === 'pending') $pending_count++;
    elseif ($status === 'rejected') $rejected_count++;
}

$current_tab = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
?>

<div class="wrap">
    <h1>Dealer Management</h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=pending-dealers&status=pending" class="nav-tab <?php echo $current_tab === 'pending' ? 'nav-tab-active' : ''; ?>">
            Pending (<?php echo $pending_count; ?>)
        </a>
        <a href="?page=pending-dealers&status=approved" class="nav-tab <?php echo $current_tab === 'approved' ? 'nav-tab-active' : ''; ?>">
            Approved (<?php echo $approved_count; ?>)
        </a>
        <a href="?page=pending-dealers&status=rejected" class="nav-tab <?php echo $current_tab === 'rejected' ? 'nav-tab-active' : ''; ?>">
            Rejected (<?php echo $rejected_count; ?>)
        </a>
    </nav>
    
    <div class="dealer-list-wrap">
        <?php
        $filter_args = array(
            'role' => 'dealer',
            'meta_query' => array(
                array(
                    'key' => '_dealer_approved',
                    'value' => $current_tab === 'approved' ? 'yes' : $current_tab,
                    'compare' => '='
                )
            ),
            'orderby' => 'registered',
            'order' => 'DESC'
        );
        
        $dealers = get_users($filter_args);
        
        if (empty($dealers)) :
        ?>
            <p>No dealers found.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Business Type</th>
                        <th>Registered</th>
                        <th>Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dealers as $dealer) : 
                        $company = get_user_meta($dealer->ID, '_dealer_company', true);
                        $phone = get_user_meta($dealer->ID, 'billing_phone', true);
                        $business_type = get_user_meta($dealer->ID, '_dealer_business_type', true);
                        $certificate = get_user_meta($dealer->ID, '_dealer_resale_certificate', true);
                        $registered = date('M j, Y', strtotime($dealer->user_registered));
                    ?>
                        <tr data-user-id="<?php echo $dealer->ID; ?>">
                            <td>
                                <strong><?php echo esc_html($company); ?></strong>
                            </td>
                            <td>
                                <?php echo esc_html($dealer->first_name . ' ' . $dealer->last_name); ?>
                            </td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($dealer->user_email); ?>">
                                    <?php echo esc_html($dealer->user_email); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($phone); ?></td>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $business_type))); ?></td>
                            <td><?php echo $registered; ?></td>
                            <td>
                                <?php if ($certificate) : ?>
                                    <a href="<?php echo esc_url($certificate); ?>" target="_blank" class="button button-small">
                                        View Certificate
                                    </a>
                                <?php else : ?>
                                    <span class="no-doc">No document</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <?php if ($current_tab === 'pending') : ?>
                                    <button type="button" class="button button-primary approve-dealer-btn" data-user-id="<?php echo $dealer->ID; ?>">
                                        Approve
                                    </button>
                                    <button type="button" class="button reject-dealer-btn" data-user-id="<?php echo $dealer->ID; ?>">
                                        Reject
                                    </button>
                                <?php elseif ($current_tab === 'approved') : ?>
                                    <span class="status-approved">✓ Approved</span>
                                <?php else : ?>
                                    <span class="status-rejected">✗ Rejected</span>
                                    <button type="button" class="button button-small approve-dealer-btn" data-user-id="<?php echo $dealer->ID; ?>">
                                        Re-approve
                                    </button>
                                <?php endif; ?>
                                
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $dealer->ID); ?>" class="button button-small">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Rejection Modal -->
    <div id="rejection-modal" class="dealer-modal" style="display: none;">
        <div class="modal-content">
            <h3>Reject Dealer Application</h3>
            <p>Please provide a reason for rejection (optional):</p>
            <textarea id="rejection-reason" rows="4" placeholder="Reason for rejection..."></textarea>
            <div class="modal-actions">
                <button type="button" class="button button-primary confirm-reject-btn">Confirm Rejection</button>
                <button type="button" class="button cancel-modal-btn">Cancel</button>
            </div>
            <input type="hidden" id="reject-user-id" value="">
        </div>
    </div>
</div>

<style>
.dealer-list-wrap {
    margin-top: 20px;
}

.actions-cell {
    white-space: nowrap;
}

.actions-cell .button {
    margin-right: 5px;
}

.status-approved {
    color: #46b450;
    font-weight: bold;
}

.status-rejected {
    color: #dc3232;
    font-weight: bold;
}

.no-doc {
    color: #999;
    font-style: italic;
}

.dealer-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 4px;
    max-width: 500px;
    width: 100%;
}

.modal-content h3 {
    margin-top: 0;
}

.modal-content textarea {
    width: 100%;
    margin-bottom: 15px;
}

.modal-actions {
    text-align: right;
}

.modal-actions .button {
    margin-left: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Approve dealer
    $('.approve-dealer-btn').on('click', function() {
        var userId = $(this).data('user-id');
        var $row = $(this).closest('tr');
        
        if (confirm('Are you sure you want to approve this dealer?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'approve_dealer',
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(function() {
                            $(this).remove();
                        });
                        alert('Dealer approved successfully!');
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        }
    });
    
    // Open rejection modal
    $('.reject-dealer-btn').on('click', function() {
        var userId = $(this).data('user-id');
        $('#reject-user-id').val(userId);
        $('#rejection-modal').show();
    });
    
    // Cancel modal
    $('.cancel-modal-btn').on('click', function() {
        $('#rejection-modal').hide();
        $('#rejection-reason').val('');
    });
    
    // Confirm rejection
    $('.confirm-reject-btn').on('click', function() {
        var userId = $('#reject-user-id').val();
        var reason = $('#rejection-reason').val();
        var $row = $('tr[data-user-id="' + userId + '"]');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'reject_dealer',
                user_id: userId,
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(function() {
                        $(this).remove();
                    });
                    $('#rejection-modal').hide();
                    $('#rejection-reason').val('');
                    alert('Dealer rejected.');
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
});
</script>
