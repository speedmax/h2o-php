<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title>{{ rss.title }}</title>
    <link>{{ rss.url }}</link>
    <description>{{ rss.description }}</description>
    <language>{{ rss.language }}</language>
    <pubDate>{{ rss.created }}</pubDate>
    <docs>http://blogs.law.harvard.edu/tech/rss</docs>
    <generator>H2O</generator>
    <managingEditor>{{ rss.user.email }}</managingEditor>
    <webMaster>webmaster@example.com</webMaster>
    {% for article in articles %} 
    <item>
      <title>{{ article.title }}</title>
      <link>{{ article.url }}</link>
      <description>{{ article.description }}</description>
      <pubDate>{{ article.created }}</pubDate>
      <guid>http://www.example.com/articles/view/{{ article.id }}</guid> 
    </item>
    {% endfor %}
  </channel>
</rss>