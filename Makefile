.PHONY: lint package clean

lint:
	find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l

package:
	bash build/release-package.sh

clean:
	rm -rf dist
