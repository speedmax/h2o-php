H2o Template Examples
----------------------
This directory contains example code covering various aspect of h2o template.

Simple
=====================
A simple template displaying a list of users, it demonstrate 

- how to setup h2o template and pass view variables to template
- display variables and using build-in tags and filters
- a basic template inheritance setup contains a layout template and page template

[Show me](simome/)

Inheritance
=====================
Shows example about how to use both template inheritance and inclusion.

[Show me](inheritance/)



Extensions
=====================
Demonstrate how to write and include custom tags, filters and loading extensions.

[Show me](extensions/)


Caching
=====================
Demonstrates

- how to use different cache driver
- bundled cache extension to provide fragment cache to speed up resource intensive operations
  such as loading complex logic from database

I18n
=====================
Demo to show how to build a internationalized template with h2o by displaying a template 
supporting three languages.

I18n extension also bundled message extraction class to extract translatable strings into
[poedit](http://google.com/search?q=poedit) friendly PO files.

[Show me](i18n/)