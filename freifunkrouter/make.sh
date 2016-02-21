#!/bin/sh
mkdir -p build/lockbox/util build/lockbox/digest

cd lockbox
cat ../0001-Dirty-hack-to-make-OpenWRT-load-the-C-Module-for-bit.patch | git am
cd ..

cp -r lockbox/lockbox/mac build/lockbox
cp lockbox/lockbox/util/bit.lua build/lockbox/util
cp lockbox/lockbox/util/stream.lua build/lockbox/util
cp lockbox/lockbox/util/array.lua build/lockbox/util
cp lockbox/lockbox/util/queue.lua build/lockbox/util
cp lockbox/lockbox/digest/sha2_256.lua build/lockbox/digest

cd lockbox
git checkout 3ce6d3e8fd2489fd9b10264401d3dc7ba7831fac
cd ..

cp raumstatus.lua  secret.lua build
