#ifndef CONFIG_W32_H
#define CONFIG_W32_H

#include <windows.h>
#include <io.h>

#ifndef inline
#	define inline   __inline
#endif

#define strcasecmp(s1, s2) _stricmp(s1, s2)
#define strncasecmp(s1, s2, n) strnicmp(s1, s2, n)

#ifndef S_ISREG 
#define S_ISREG(m) (((m) & S_IFMT) == S_IFREG)
#endif

#ifndef logf 
#define logf(x)     ((float)log((double)(x)))
#endif

#endif
