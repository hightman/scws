<?php
// $Id$
// generate all gbk chars (not include symbols)
//

// GBK1
for($i=0xa1; $i<=0xa9; $i++)
  for($j=0xa1; $j<=0xfe; $j++)
    echo chr($i).chr($j)."\n";

// GBK5
for($i=0xa8; $i<=0xa9; $i++)
  for($j=0x40; $j<=0xa0; $j++)
    echo chr($i).chr($j)."\n";
exit;

// GBK/2 
for($i=0xb0; $i<=0xf7; $i++)
  for($j=0xa1; $j<=0xfe;$j++)  
    echo chr($i).chr($j)."\n";

// gb13000.1
// GBK/3
// 0x8140 ~ 0xa0fe
for($i=0x81; $i<=0xa0; $i++)
  for($j=0x40; $j<=0xfe; $j++)
    echo chr($i).chr($j)."\n";

// GBK4
// 0xaa40 ~ 0xfea0
for($i=0xaa; $i<=0xfe; $i++)
  for($j=0x40; $j<=0xa0; $j++)
    echo chr($i).chr($j)."\n";

