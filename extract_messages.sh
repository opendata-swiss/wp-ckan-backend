#!/bin/bash

HERE=`dirname $0`
(find $HERE \
     -not -path "vendor/*" \
     -not -path "bin/*" \
     -type f -iregex .*php$ \
     | xargs xgettext --language=PHP --from-code=UTF-8 --no-wrap --foreign-user --package-name="wp-ckan-backend" --package-version=1.0.0 --msgid-bugs-address=jazz@liip.ch \
     --keyword=__ \
     --keyword=_e \
     --keyword=__ngettext:1,2 \
     --keyword=_n:1,2 \
     --keyword=__ngettext_noop:1,2 \
     --keyword=_n_noop:1,2 \
     --keyword=_c \
     --keyword=_nc:4c,1,2 \
     --keyword=_x:1,2c \
     --keyword=_nx:4c,1,2 \
     --keyword=_nx_noop:4c,1,2 \
     --keyword=_ex:1,2c \
     --keyword=esc_attr__ \
     --keyword=esc_attr_e \
     --keyword=esc_attr_x:1,2c \
     --keyword=esc_html__ \
     --keyword=esc_html_e \
     --keyword=esc_html_x:1,2c \
     -o "languages/wp-ckan-backend.pot" -
)
