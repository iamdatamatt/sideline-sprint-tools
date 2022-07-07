<style>
  a[target="_blank"][class="dropdown-item"]::after {
    content: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAQElEQVR42qXKwQkAIAxDUUdxtO6/RBQkQZvSi8I/pL4BoGw/XPkh4XigPmsUgh0626AjRsgxHTkUThsG2T/sIlzdTsp52kSS1wAAAABJRU5ErkJggg==);
    margin: 0 3px 0 5px;
    filter: invert(100%) sepia(0%) saturate(1666%) hue-rotate(24deg) brightness(95%) contrast(95%);
  }
</style>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark navbar-border">
  <a class="navbar-brand" href="/">
    <img src="https://cdn-tools.sidelinesprint.com/img/icon-192x192.png" data-src="https://cdn-tools.sidelinesprint.com/img/icon-192x192.png" width="30" height="30" class="d-inline-block align-top lazyload" alt="..." />
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" style="border-color:rgba(255,255,255,0) !important;">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a id="directory-link" class="nav-link" href="/">Tools Directory</a>
      </li>
      <li class="nav-item dropdown">
        <a id="internal-tools-link" class="nav-link dropdown-toggle" href="#" id="navbarDropdown1" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="outline:none!important;">Internal Tools</a>
        <div class="dropdown-menu dropdown-border-override" aria-labelledby="navbarDropdown1">
          <a id="beehiiv-overview-link" class="dropdown-item" href="/insights/beehiiv-overview">Beehiiv Overview</a>
          <a id="website-article-uploader-link" class="dropdown-item" href="/website/website-article-uploader">Website Article Uploader</a>
          <a id="image-uploader-link" class="dropdown-item" href="/newsletter/image-uploader">Image Uploader</a>
          <?php if ($user_role === "admin") { ?>
            <a id="existing-subscriber-management-link" class="dropdown-item" href="/subscribers/single-subscriber-search">Single Subscriber Search</a>
            <a id="bulk-subscriber-management-link" class="dropdown-item" href="/subscribers/bulk-subscriber-search">Bulk Subscriber Search</a>
          <?php } ?>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a id="external-tools-link" class="nav-link dropdown-toggle" href="#" id="navbarDropdown2" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="outline:none!important;">External Tools</a>
        <div class="dropdown-menu dropdown-border-override" aria-labelledby="navbarDropdown2">
          <a id="beehiiv-link" class="dropdown-item" href="https://app.beehiiv.com" target="_blank">Beehiiv Portal</a>
          <a id="file-storage-link" class="dropdown-item" href="https://panel.bunny.net/" target="_blank">Content Delivery Network</a>
          <a id="password-manager-link" class="dropdown-item" href="https://vault.bitwarden.com/#/vault" target="_blank">Password Manager</a>
          <a id="transactional-provider-link" class="dropdown-item" href="https://account.postmarkapp.com/servers" target="_blank">Transactional Email Portal</a>
          <a id="newsletter-draft-docs-link" class="dropdown-item" href="-" target="_blank">Google Drive</a>
          <a id="blog-portal-link" class="dropdown-item" href="https://www.sidelinesprint.com/home/ghost" target="_blank">Blog Portal</a>
          <a id="search-console-link" class="dropdown-item" href="-" target="_blank">Google Search Console</a>
          <a id="google-analytics-link" class="dropdown-item" href="-" target="_blank">Google Analytics</a>
          <a id="postmaster-tools-link" class="dropdown-item" href="-" target="_blank">Google Postmaster Tools</a>
          <a id="dmarc-digests-link" class="dropdown-item" href="-" target="_blank">DMARC Monitoring</a>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a id="brand-links-link" class="nav-link dropdown-toggle" href="#" id="navbarDropdown4" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="outline:none!important;">Brand Links</a>
        <div class="dropdown-menu dropdown-border-override" aria-labelledby="navbarDropdown4">
          <a id="homepage-link" class="dropdown-item" href="https://www.sidelinesprint.com" target="_blank">Homepage</a>
          <a id="blog-homepage-link" class="dropdown-item" href="https://www.sidelinesprint.com/home/" target="_blank">Blog</a>
          <a id="twitter-link" class="dropdown-item" href="https://twitter.com/sidelinesprint" target="_blank">Twitter</a>
          <a id="instagram-link" class="dropdown-item" href="https://www.instagram.com/SidelineSprint/" target="_blank">Instagram</a>
          <a id="facebook-link" class="dropdown-item" href="https://www.facebook.com/sidelinesprint" target="_blank">Facebook</a>
          <a id="linkedin-link" class="dropdown-item" href="https://www.linkedin.com/company/sidelinesprint/" target="_blank">LinkedIn</a>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a id="internal-insights-link" class="nav-link dropdown-toggle" href="#" id="navbarDropdown5" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="outline:none!important;">Deprecated Tools</a>
        <div class="dropdown-menu dropdown-border-override" aria-labelledby="navbarDropdown5">
          <a id="newsletter-editor-link" class="dropdown-item" href="/newsletter/newsletter-editor">Newsletter Editor</a>
          <a id="manual-newsletter-uploader-link" class="dropdown-item" href="/newsletter/manual-newsletter-uploader">Manual Newsletter Uploader</a>
          <a id="overview-dashboard-link" class="dropdown-item" href="/insights/overview-dashboard">Overview Dashboard</a>
          <a id="transactional-manager-link" class="dropdown-item" href="/transactional/transactional-overview">Transactional Overview</a>
          <a id="campaign-manager-link" class="dropdown-item" href="/campaigns/campaign-overview">Campaign Overview</a>
          <a id="list-manager-link" class="dropdown-item" href="/lists/list-overview">List Overview</a>
          <a id="journey-manager-link" class="dropdown-item" href="/journeys/journey-overview">Journey Overview</a>
          <a id="referral-overview-link" class="dropdown-item" href="/insights/referral-overview">Referral Overview</a>
          <a id="ambassador-overview-link" class="dropdown-item" href="/insights/ambassador-overview">Ambassador Overview</a>
          <a id="email-portal-link" class="dropdown-item" href="https://read.sidelinesprint.com" target="_blank">Campaign Monitor Portal</a>
        </div>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item d-none d-lg-block">
        <a id="name-link" name="name-link" class="nav-link" style="padding-top:12px"></a>
      </li>
      <li class="nav-item dropdown">
        <a id="profile-pic-link" class="nav-link dropdown-toggle" href="#" id="navbarDropdown3" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="outline:none!important;">
          <img src="https://cdn-tools.sidelinesprint.com/img/lazyload-30x30.png" data-src="https://cdn-tools.sidelinesprint.com/img/lazyload-30x30.png" width="30" height="30" class="d-inline-block align-top lazyload" alt="..." id="user-pic" name="user-pic" style="border-radius:50%;border:2px solid #67ca88;" />
        </a>
        <div class="dropdown-menu dropdown-menu-right dropdown-border-override" aria-labelledby="navbarDropdown3">
          <a id="email-link" name="email-link" class="dropdown-item"></a>
          <a id="logout-link" class="dropdown-item" href="/logout">Logout</a>
        </div>
      </li>
    </ul>
  </div>
</nav>
