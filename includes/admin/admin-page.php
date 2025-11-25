<?php 


function yap_admin_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['yap_save_group'])) {
        yap_save_group();
    }

    ?>
    <div class="wrap">
        <h1>Yet Another Plugin</h1>
        <form id="add-group-form">
            <table class="form-table">
                <tr>
                    <th><label for="group_name">Nazwa grupy:</label></th>
                    <td>
                        <input type="text" id="group_name" name="group_name" placeholder="Nazwa grupy" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="post_type">Typ posta:</label></th>
                    <td>
                        <select id="post_type" name="post_type">
                            <option value="">Wszystkie typy postów</option>
                            <?php
                            $post_types = get_post_types(['public' => true], 'objects');
                            foreach ($post_types as $post_type) {
                                echo '<option value="' . esc_attr($post_type->name) . '">' . esc_html($post_type->label) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Wybierz gdzie ma się wyświetlać grupa pól</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="category">Kategoria:</label></th>
                    <td>
                        <select id="category" name="category">
                            <option value="">Wszystkie kategorie</option>
                            <?php
                            $categories = get_categories();
                            foreach ($categories as $category) {
                                echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Działa tylko dla taxonomii "category" (standardowe kategorie WP)</p>
                    </td>
                </tr>
            </table>
            <button type="submit" class="button button-primary">Zapisz grupę</button>
        </form>
    </div>
    <?php
}

?>