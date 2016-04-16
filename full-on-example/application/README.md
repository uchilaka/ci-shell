## CAUTION: Symlinks On Board

We've made some use of symlinks within directoies in this example to keep the actual file in your 
(linux) folder fresh from the library path. At worst, these won't work in your environment 
(Windows, anyone?). If that's the case for you, look at the raw value in the symlink file (looks like this):

```
third_party/uchilaka/ci-shell/copy-contents-to-ci-root/application    
```

for a symlink that ends in a directory, and like this for one that ends in a file:

```
../shell/libraries/OAuth2lib.php
```
to see the file whose contents you need to copy in place of the link file. The paths will always be 
relative to the spot right where the symlink file is at.

In a UNIX (OSX / Linux) environment, these function like shortcuts in windows.

If you have any questions about this, [drop me a line on Twitter](https://twitter.com/intent/tweet?via=uchechilaka&text=Question about symlinks in uchilaka/ci-shell). The objective here was to not 
have to keep track of multiple copies of the same file - and to give you an idea how the 
custom files map into your CodeIgniter project. 

Happy coding!