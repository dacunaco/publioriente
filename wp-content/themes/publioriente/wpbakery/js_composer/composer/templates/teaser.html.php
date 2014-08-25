<script type="text/html" id="vc-teaser-button">
    <div class="vc-teaser-checkbox">
        <label class="vc-teaser-button-label vc-teaser-label-{{ value }}"><input class="vc-teaser-button vc-teaser-btn-{{ value }}" type="checkbox" value="{{value}}"> <span>{{label}}</span></label>
    </div>
</script>
<script type="text/html" id="vc-teaser-title">
    <div class="vc-teaser-control vc-teaser-ctr-{{ name }}" data-control="{{ name }}" id="vc-teaser-title-control">
        <div class="vc-move"></div>
        <span></span>
        <div class="vc-link-controls">
            <?php _e('Link to', LANGUAGE_ZONE) ?>: <a href="#" class="vc-link-control{{ link === 'none' ? ' vc-active-link' : ''}}" data-link="none"><?php _e('No link', LANGUAGE_ZONE) ?></a>
            | <a href="#" class="vc-link-control{{ link === 'post' ? ' vc-active-link' : ''}}" data-link="post"><?php _e('On post', LANGUAGE_ZONE) ?></a>
            | <a href="#" class="vc-link-control{{ link === 'big_image' ? ' vc-active-link' : ''}}" data-link="big_image"><?php _e('Big image', LANGUAGE_ZONE) ?></a>
        </div>
    </div>
</script>
<script type="text/html" id="vc-teaser-image">
    <div class="vc-teaser-control vc-teaser-ctr-{{ name }}" data-control="{{ name }}" id="vc-teaser-image-control">
        <div class="vc-move"></div>
        <div class="vc-buttons">
            <a href="#" class="vc-teaser-image-featured" data-mode="featured"><?php _e('Featured', LANGUAGE_ZONE) ?></a> |
            <a href="#" class="vc-teaser-image-custom" data-mode="custom"><?php _e('Custom', LANGUAGE_ZONE) ?></a>
        </div>
        <div class="vc-image">
        </div>
        <div class="vc-link-controls">
            <?php _e('Link to', LANGUAGE_ZONE) ?>: <a href="#" class="vc-link-control{{ link === 'none' ? ' vc-active-link' : ''}}" data-link="none"><?php _e('No link', LANGUAGE_ZONE) ?></a>
            | <a href="#" class="vc-link-control{{ link === 'post' ? ' vc-active-link' : ''}}" data-link="post"><?php _e('On post', LANGUAGE_ZONE) ?></a>
            | <a href="#" class="vc-link-control{{ link === 'big_image' ? ' vc-active-link' : ''}}" data-link="big_image"><?php _e('Big image', LANGUAGE_ZONE) ?></a>
        </div>
    </div>
</script>
<script type="text/html" id="vc-teaser-text">
    <div class="vc-teaser-control vc-teaser-ctr-{{ name }}" data-control="{{ name }}" id="vc-teaser-text-control">
        <div class="vc-move"></div>
        <div class="vc-buttons">
            <a href="#" class="vc-teaser-text-excerpt vc-teaser-text-control" data-mode="excerpt"><?php _e('Excerpt', LANGUAGE_ZONE) ?></a> |
            <a href="#" class="vc-teaser-text-text vc-teaser-text-control" data-mode="text"><?php _e('Text', LANGUAGE_ZONE) ?></a> |
            <a href="#" class="vc-teaser-text-custom vc-teaser-text-control" data-mode="custom"><?php _e('Custom', LANGUAGE_ZONE) ?></a>
        </div>
        <div class="vc-text">
        </div>
    </div>
</script>
<script type="text/html" id="vc-teaser-link">
    <div class="vc-teaser-control vc-teaser-ctr-{{ name }}" data-control="{{ name }}" id="vc-teaser-link-control">
        <div class="vc-move"></div>
        <a href="#"><?php _e('Read more', LANGUAGE_ZONE) ?></a>
    </div>
</script>
<script type="text/html" id="vc-teaser-custom-image-block">
    <div class="vc-custom">
        <div class="vc-teaser-custom-image-view">

        </div>
        <a class="vc_teaser_add_custom_image" href="#" title="<?php _e('Add custom image', LANGUAGE_ZONE) ?>"><?php _e('Add custom image', LANGUAGE_ZONE) ?></a>

    </div>
</script>
<script type="text/html" id="vc-teaser-custom-image">
    <a href="#" class="vc_teaser_add_custom_image" style="width: 266px; text-align: center;">
        <img rel="<%= id %>" src="<%= url %>" />
    </a>
</script>