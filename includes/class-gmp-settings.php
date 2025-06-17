<?php

class GMP_Settings {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_settings_menu() {
        add_submenu_page(
            'woocommerce',
            'Gold Money Plan Settings',
            'Gold Money Plan',
            'manage_options',
            'gmp-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function register_settings() {
        register_setting('gmp_settings_group', 'gmp_interest_settings');
    }

    public static function render_settings_page() {
        $data = get_option('gmp_interest_settings', []);
        $products = wc_get_products(['type' => 'variable-subscription', 'limit' => -1]);

        ?>
        <div class="wrap">
            <h1>Gold Money Plan – Interest & Extension Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('gmp_settings_group'); ?>
                <table id="gmp_interest_table" class="widefat">
                    <thead>
                        <tr>
                            <th>Plan Product</th>
                            <th>Base Interest (%)</th>
                            <th>Enable Extension</th>
                            <th>Months</th>
                            <th>Extension Interest Per Month</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $index => $row): ?>
                            <tr>
                                <td>
                                    <select name="gmp_interest_settings[<?php echo $index; ?>][product_id]">
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?= $p->get_id(); ?>" <?= selected($row['product_id'], $p->get_id()) ?>><?= $p->get_name(); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="gmp_interest_settings[<?php echo $index; ?>][interest]" value="<?= esc_attr($row['interest']) ?>"></td>
                                <td><input type="checkbox" name="gmp_interest_settings[<?php echo $index; ?>][extension]" <?= isset($row['extension']) ? 'checked' : '' ?>></td>
                                <td><input type="number" name="gmp_interest_settings[<?php echo $index; ?>][months]" value="<?= esc_attr($row['months'] ?? '') ?>"></td>
                                <td>
                                    <?php
                                    if (!empty($row['months']) && !empty($row['ext_interests'])) {
                                        for ($i = 1; $i <= $row['months']; $i++) {
                                            echo "Month {$i}: <input type='number' name='gmp_interest_settings[{$index}][ext_interests][{$i}]' value='" . esc_attr($row['ext_interests'][$i] ?? '') . "' style='width:60px' /> ";
                                        }
                                    }
                                    ?>
                                </td>
                                <td><button type="button" class="button remove-row">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="add_interest_row">Add Plan</button></p>
                <?php submit_button(); ?>
            </form>
        </div>

        <script>
            document.getElementById('add_interest_row').addEventListener('click', function () {
                const tbody = document.querySelector('#gmp_interest_table tbody');
                const index = tbody.rows.length;
                const newRow = document.createElement('tr');

                newRow.innerHTML = `
                    <td>
                        <select name="gmp_interest_settings[${index}][product_id]">
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p->get_id(); ?>"><?= $p->get_name(); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" name="gmp_interest_settings[${index}][interest]" value=""></td>
                    <td><input type="checkbox" name="gmp_interest_settings[${index}][extension]"></td>
                    <td><input type="number" name="gmp_interest_settings[${index}][months]" value=""></td>
                    <td></td>
                    <td><button type="button" class="button remove-row">Remove</button></td>
                `;
                tbody.appendChild(newRow);
            });

            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-row')) {
                    e.target.closest('tr').remove();
                }
            });
        </script>
        <?php
    }
}
