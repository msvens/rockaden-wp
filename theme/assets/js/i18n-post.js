/**
 * Client-side i18n for server-rendered post blocks.
 * Reformats dates, translates UI strings, and injects comment buttons.
 */
(function () {
  var strings = {
    sv: {
      readMore: 'Läs mer',
      comments: 'Kommentarer',
      leaveReply: 'Lämna en kommentar',
      comment: 'Kommentera',
      commentLabel: 'Kommentar',
      oneComment: '1 kommentar',
      nComments: ' kommentarer',
      reply: 'Svara',
      postComment: 'Skicka kommentar',
    },
    en: {
      readMore: 'Read more',
      comments: 'Comments',
      leaveReply: 'Leave a Reply',
      comment: 'Comment',
      commentLabel: 'Comment',
      oneComment: '1 Comment',
      nComments: ' Comments',
      reply: 'Reply',
      postComment: 'Post Comment',
    },
  };

  var dateFormatters = {};
  function getFormatter(lang) {
    if (!dateFormatters[lang]) {
      var locale = lang === 'sv' ? 'sv-SE' : 'en-US';
      dateFormatters[lang] = new Intl.DateTimeFormat(locale, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });
    }
    return dateFormatters[lang];
  }

  function commentLabel(s, count) {
    if (!count) return s.comment;
    if (count === 1) return s.oneComment;
    return count + s.nComments;
  }

  // Inject comment buttons after each "Läs mer" link in post lists
  function injectCommentButtons(s) {
    var excerpts = document.querySelectorAll(
      '.rockaden-post-list .wp-block-post-excerpt'
    );
    if (!excerpts.length) return;

    // Collect post URLs and create buttons
    var buttons = [];
    excerpts.forEach(function (excerpt) {
      // Skip if already injected
      if (excerpt.parentNode.querySelector('.rockaden-comment-btn')) return;

      // Find the post URL from the title link in the same post-content group
      var content = excerpt.closest('.rockaden-post-content');
      if (!content) return;
      var titleLink = content.querySelector('.wp-block-post-title a');
      if (!titleLink) return;

      // Insert next to "Läs mer" link inside the excerpt block
      var moreLink = excerpt.querySelector('.wp-block-post-excerpt__more-link');
      if (!moreLink) return;

      var btn = document.createElement('a');
      btn.href = titleLink.href + '#respond';
      btn.className = 'rockaden-comment-btn';
      btn.textContent = s.comment;
      moreLink.parentNode.insertBefore(btn, moreLink.nextSibling);
      buttons.push({ el: btn, url: titleLink.href });
    });

    // Fetch comment counts to update button labels
    if (buttons.length) {
      fetchCommentCounts(s, buttons);
    }
  }

  function fetchCommentCounts(s, buttons) {
    // Build a map of URL → button elements
    var urlMap = {};
    buttons.forEach(function (b) {
      // Normalize URL by stripping trailing slash
      var key = b.url.replace(/\/$/, '');
      if (!urlMap[key]) urlMap[key] = [];
      urlMap[key].push(b.el);
    });

    // Use WP REST API to get posts with comment counts
    var apiUrl = '/wp-json/wp/v2/posts?per_page=20&_fields=id,link,comment_count';
    var xhr = new XMLHttpRequest();
    xhr.open('GET', apiUrl);
    xhr.onload = function () {
      if (xhr.status !== 200) return;
      try {
        var posts = JSON.parse(xhr.responseText);
        posts.forEach(function (post) {
          var key = post.link.replace(/\/$/, '');
          var els = urlMap[key];
          if (els && post.comment_count > 0) {
            els.forEach(function (el) {
              el.textContent = commentLabel(s, post.comment_count);
            });
          }
        });
      } catch (e) {
        // Silently fail — buttons keep default "Kommentera" text
      }
    };
    xhr.send();
  }

  function updateCommentButtonLabels(s) {
    document.querySelectorAll('.rockaden-comment-btn').forEach(function (el) {
      // Re-parse count from current text or reset to default
      var match = el.textContent.match(/^(\d+)/);
      if (match) {
        var count = parseInt(match[1], 10);
        el.textContent = commentLabel(s, count);
      } else {
        el.textContent = s.comment;
      }
    });
  }

  function update() {
    var lang = document.documentElement.getAttribute('data-lang') || 'sv';
    var s = strings[lang] || strings.sv;
    var fmt = getFormatter(lang);

    // Reformat post dates (both post dates and comment dates)
    document.querySelectorAll(
      '.wp-block-post-date time[datetime], .wp-block-comment-date time[datetime]'
    ).forEach(function (el) {
      var date = new Date(el.getAttribute('datetime'));
      if (!isNaN(date.getTime())) {
        el.textContent = fmt.format(date);
      }
    });

    // Swap "Läs mer" / "Read more"
    document.querySelectorAll('.wp-block-post-excerpt__more-link').forEach(function (el) {
      el.textContent = s.readMore;
    });

    // Inject comment buttons in post list (only on first run)
    injectCommentButtons(s);

    // Update existing comment button labels on language change
    updateCommentButtonLabels(s);

    // Translate comments title ("One response to ..." → "Kommentarer")
    document.querySelectorAll('.wp-block-comments-title').forEach(function (el) {
      el.textContent = s.comments;
    });

    // Translate "Leave a Reply"
    document.querySelectorAll('.comment-reply-title').forEach(function (el) {
      var cancelLink = el.querySelector('small');
      el.textContent = s.leaveReply + ' ';
      if (cancelLink) el.appendChild(cancelLink);
    });

    // Translate "Reply" links
    document.querySelectorAll('.wp-block-comment-reply-link a, .comment-reply-link a').forEach(
      function (el) {
        el.textContent = s.reply;
      }
    );

    // Translate "Post Comment" button
    document.querySelectorAll('.form-submit input[type="submit"]').forEach(function (el) {
      el.value = s.postComment;
    });

    // Translate "Comment" label on textarea
    document.querySelectorAll('.comment-form-comment label[for="comment"]').forEach(function (el) {
      var required = el.querySelector('.required');
      el.textContent = s.commentLabel + ' ';
      if (required) el.appendChild(required);
    });
  }

  // Run on load and on language change
  update();
  window.addEventListener('rockaden-lang-change', update);
})();
