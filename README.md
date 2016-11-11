# IP tools
Сlass to work with IPv4 and IPv6 addresses

## Methods IPv4
- **ip4_mask_to_prefix($mask)** - Convert mask to prefix subnet
- **ip4_prefix_to_mask($prefix)** - Convert prefix to mask subnet
- **ip4_to_range($addr, $mask)** - Convert ip subnet to ip start and ip end
- **ip4_validate($addr)** - Validate ipv4 address
- **ip4_to_int($addr)** - Convert ipv4 to int
- **int_to_ip4($int_addr)** - Convert int to ipv4

## Methods IPv6
- **ip6_expand($addr)** - Expand an IPv6 Address
- **ip6_compress($addr)** - Compress an IPv6 Address
- **ip6_prefix_to_mask($prefix)** - Generate an IPv6 mask from prefix notation
- **ip6_mask_to_prefix($mask)** - Generate an IPv6 prefix from mask notation
- **ip6_to_range($addr, $mask)** - Convert an IPv6 address and prefix size to an address range for the network.
- **ip6_validate($addr)** - Validate ipv6 address
- **ip6_to_numeric($addr)** - Convert ipv6 to numeric
- **ip6_to_bin($addr)** - Convert ipv6 to bin
- **ip6_to_split_int($addr)** - Convert an IPv6 address to two 64-bit integers.
- **split_int_to_ip6($val)** - Convert two 64-bit integer values into an IPv6 address
- **numeric_to_ip6($dec)** - Convert numeric to ipv6
- **bin_to_ip6($bin)** - Convert bin to ipv6
