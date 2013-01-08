# Microsoft Developer Studio Project File - Name="scws" - Package Owner=<4>
# Microsoft Developer Studio Generated Build File, Format Version 6.00
# ** DO NOT EDIT **

# TARGTYPE "Win32 (x86) Dynamic-Link Library" 0x0102

CFG=scws - Win32 Debug_PHP4
!MESSAGE This is not a valid makefile. To build this project using NMAKE,
!MESSAGE use the Export Makefile command and run
!MESSAGE 
!MESSAGE NMAKE /f "scws.mak".
!MESSAGE 
!MESSAGE You can specify a configuration when running NMAKE
!MESSAGE by defining the macro CFG on the command line. For example:
!MESSAGE 
!MESSAGE NMAKE /f "scws.mak" CFG="scws - Win32 Debug_PHP4"
!MESSAGE 
!MESSAGE Possible choices for configuration are:
!MESSAGE 
!MESSAGE "scws - Win32 Debug_PHP4" (based on "Win32 (x86) Dynamic-Link Library")
!MESSAGE "scws - Win32 Release_PHP4" (based on "Win32 (x86) Dynamic-Link Library")
!MESSAGE "scws - Win32 Debug_PHP5" (based on "Win32 (x86) Dynamic-Link Library")
!MESSAGE "scws - Win32 Release_PHP5" (based on "Win32 (x86) Dynamic-Link Library")
!MESSAGE "scws - Win32 Debug_PHP53" (based on "Win32 (x86) Dynamic-Link Library")
!MESSAGE "scws - Win32 Release_PHP53" (based on "Win32 (x86) Dynamic-Link Library")
!MESSAGE 

# Begin Project
# PROP AllowPerConfigDependencies 0
# PROP Scc_ProjName ""
# PROP Scc_LocalPath ""
CPP=cl.exe
MTL=midl.exe
RSC=rc.exe

!IF  "$(CFG)" == "scws - Win32 Debug_PHP4"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "scws___Win32_Debug_PHP4"
# PROP BASE Intermediate_Dir "scws___Win32_Debug_PHP4"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "../Debug"
# PROP Intermediate_Dir "../Debug"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MD /W3 /GX /Zi /O2 /I "..\..\php-4.4.9" /I "..\..\php-4.4.9\main" /I "..\..\php-4.4.9\TSRM" /I "..\..\php-4.4.9\win32" /I "..\..\php-4.4.9\Zend" /D "WIN32" /D "NDEBUG" /D "_WINDOWS" /D "_MBCS" /D "_USRDLL" /D "SCWS_EXPORTS" /D "PHP_WIN32" /D "ZEND_WIN32" /D ZTS=1 /D ZEND_DEBUG=0 /D "COMPILE_DL_SCWS" /Fr /YX /FD /c
# ADD CPP /nologo /MD /W3 /Gm /GX /Zi /Od /I "..\..\php-4.4.9" /I "..\..\php-4.4.9\main" /I "..\..\php-4.4.9\TSRM" /I "..\..\php-4.4.9\win32" /I "..\..\php-4.4.9\Zend" /I "..\libscws" /D "_WINDOWS" /D "_USRDLL" /D "SCWS_EXPORTS" /D ZTS=1 /D "COMPILE_DL_SCWS" /D "_USE_32BIT_TIME_T" /D "NDEBUG" /D "WIN32" /D "_MBCS" /D "PHP_WIN32" /D "ZEND_WIN32" /D ZEND_DEBUG=0 /Fr /YX /FD /c
# ADD BASE MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x804 /d "NDEBUG"
# ADD RSC /l 0x417 /d "NDEBUG" /d "_DEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 zlib.lib php4ts.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /machine:I386
# SUBTRACT BASE LINK32 /debug
# ADD LINK32 libscws.lib zlib.lib php4ts.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /debug /machine:I386 /out:"../Debug/php-4.4.x/php_scws.dll" /libpath:"../Debug;../../php-4.4.9"

!ELSEIF  "$(CFG)" == "scws - Win32 Release_PHP4"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "scws___Win32_Release_PHP4"
# PROP BASE Intermediate_Dir "scws___Win32_Release_PHP4"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "../Release"
# PROP Intermediate_Dir "../Release"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MT /W3 /GX /Zi /O2 /D "WIN32" /D "NDEBUG" /D "_WINDOWS" /D "_MBCS" /D "_USRDLL" /D "SCWS_EXPORTS" /D "PHP_WIN32" /D "ZEND_WIN32" /D "ZTS" /D ZEND_DEBUG=0 /D "COMPILE_DL_SCWS" /YX /FD /c
# SUBTRACT BASE CPP /Fr
# ADD CPP /nologo /MD /W3 /GX /O2 /I "..\..\php-4.4.9" /I "..\..\php-4.4.9\main" /I "..\..\php-4.4.9\TSRM" /I "..\..\php-4.4.9\win32" /I "..\..\php-4.4.9\Zend" /I "..\libscws" /D "_WINDOWS" /D "_USRDLL" /D "SCWS_EXPORTS" /D ZTS=1 /D "COMPILE_DL_SCWS" /D "_USE_32BIT_TIME_T" /D "NDEBUG" /D "WIN32" /D "_MBCS" /D "PHP_WIN32" /D "ZEND_WIN32" /D ZEND_DEBUG=0 /Fr /YX /FD /c
# ADD BASE MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x804 /d "NDEBUG"
# ADD RSC /l 0x804 /d "NDEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /machine:I386
# ADD LINK32 libscws.lib zlib.lib php4ts.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /pdb:none /machine:I386 /out:"../Release/php-4.4.x/php_scws.dll" /libpath:"../Release;../../php-4.4.9"
# SUBTRACT LINK32 /debug

!ELSEIF  "$(CFG)" == "scws - Win32 Debug_PHP5"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "scws___Win32_Debug_PHP5"
# PROP BASE Intermediate_Dir "scws___Win32_Debug_PHP5"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "../Debug"
# PROP Intermediate_Dir "../Debug"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MD /W3 /GX /Zi /O2 /I "..\..\php-5.2.12" /I "..\..\php-5.2.12\main" /I "..\..\php-5.2.12\TSRM" /I "..\..\php-5.2.12\win32" /I "..\..\php-5.2.12\Zend" /D "WIN32" /D "NDEBUG" /D "_WINDOWS" /D "_MBCS" /D "_USRDLL" /D "SCWS_EXPORTS" /D "PHP_WIN32" /D "ZEND_WIN32" /D ZTS=1 /D ZEND_DEBUG=0 /D "COMPILE_DL_SCWS" /Fr /YX /FD /c
# ADD CPP /nologo /MD /W3 /Gm /GX /Zi /O2 /I "..\..\php-5.2.12" /I "..\..\php-5.2.12\main" /I "..\..\php-5.2.12\TSRM" /I "..\..\php-5.2.12\win32" /I "..\..\php-5.2.12\Zend" /I "..\libscws" /D "_WINDOWS" /D "_USRDLL" /D "SCWS_EXPORTS" /D ZTS=1 /D "COMPILE_DL_SCWS" /D "_USE_32BIT_TIME_T" /D "NDEBUG" /D "WIN32" /D "_MBCS" /D "PHP_WIN32" /D "ZEND_WIN32" /D ZEND_DEBUG=0 /Fr /YX /FD /c
# ADD BASE MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x804 /d "NDEBUG"
# ADD RSC /l 0x804 /d "NDEBUG" /d "_DEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 zlib.lib php5ts.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /debug /machine:I386
# SUBTRACT BASE LINK32 /pdb:none
# ADD LINK32 libscws.lib zlib.lib php5ts.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /debug /machine:I386 /out:"../Debug/php-5.2.x/php_scws.dll" /libpath:"../Debug;../../php-5.2.12"
# SUBTRACT LINK32 /pdb:none

!ELSEIF  "$(CFG)" == "scws - Win32 Release_PHP5"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "scws___Win32_Release_PHP5"
# PROP BASE Intermediate_Dir "scws___Win32_Release_PHP5"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "../Release"
# PROP Intermediate_Dir "../Release"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MT /W3 /GX /Zi /O2 /D "WIN32" /D "NDEBUG" /D "_WINDOWS" /D "_MBCS" /D "_USRDLL" /D "SCWS_EXPORTS" /D "PHP_WIN32" /D "ZEND_WIN32" /D "ZTS" /D ZEND_DEBUG=0 /D "COMPILE_DL_SCWS" /YX /FD /c
# SUBTRACT BASE CPP /Fr
# ADD CPP /nologo /MD /W3 /GX /O2 /I "..\..\php-5.2.12" /I "..\..\php-5.2.12\main" /I "..\..\php-5.2.12\TSRM" /I "..\..\php-5.2.12\win32" /I "..\..\php-5.2.12\Zend" /I "..\libscws" /D "_WINDOWS" /D "_USRDLL" /D "SCWS_EXPORTS" /D ZTS=1 /D "COMPILE_DL_SCWS" /D "_USE_32BIT_TIME_T" /D "NDEBUG" /D "WIN32" /D "_MBCS" /D "PHP_WIN32" /D "ZEND_WIN32" /D ZEND_DEBUG=0 /Fr /YX /FD /c
# ADD BASE MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x804 /d "NDEBUG"
# ADD RSC /l 0x804 /d "NDEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /machine:I386
# ADD LINK32 libscws.lib zlib.lib php5ts.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /pdb:none /machine:I386 /out:"../Release/php-5.2.x/php_scws.dll" /libpath:"../Release;../../php-5.2.12"
# SUBTRACT LINK32 /debug

!ELSEIF  "$(CFG)" == "scws - Win32 Debug_PHP53"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "scws___Win32_Debug_PHP53"
# PROP BASE Intermediate_Dir "scws___Win32_Debug_PHP53"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "../Debug"
# PROP Intermediate_Dir "../Debug"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MD /W3 /GX /Zi /O2 /I "..\..\php-5.3.1" /I "..\..\php-5.3.1\main" /I "..\..\php-5.3.1\TSRM" /I "..\..\php-5.3.1\win32" /I "..\..\php-5.3.1\Zend" /D "WIN32" /D "NDEBUG" /D "_WINDOWS" /D "_MBCS" /D "_USRDLL" /D "SCWS_EXPORTS" /D "PHP_WIN32" /D "ZEND_WIN32" /D ZTS=1 /D ZEND_DEBUG=0 /D "COMPILE_DL_SCWS" /Fr /YX /FD /c
# ADD CPP /nologo /MD /W3 /Gm /GX /Zi /O2 /I "..\..\php-5.3.1" /I "..\..\php-5.3.1\main" /I "..\..\php-5.3.1\TSRM" /I "..\..\php-5.3.1\win32" /I "..\..\php-5.3.1\Zend" /I "..\libscws" /D "_WINDOWS" /D "_USRDLL" /D "SCWS_EXPORTS" /D ZTS=1 /D "COMPILE_DL_SCWS" /D "_USE_32BIT_TIME_T" /D "NDEBUG" /D "WIN32" /D "_MBCS" /D "PHP_WIN32" /D "ZEND_WIN32" /D ZEND_DEBUG=0 /Fr /YX /FD /c
# ADD BASE MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x804 /d "NDEBUG"
# ADD RSC /l 0x804 /d "NDEBUG" /d "_DEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 zlib.lib php5ts.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /debug /machine:I386
# SUBTRACT BASE LINK32 /pdb:none
# ADD LINK32 libscws.lib zlib.lib php5ts.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /debug /machine:I386 /out:"../Debug/php-5.3.x_vc6/php_scws.dll" /libpath:"../Debug;../../php-5.3.1"
# SUBTRACT LINK32 /pdb:none

!ELSEIF  "$(CFG)" == "scws - Win32 Release_PHP53"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "scws___Win32_Release_PHP53"
# PROP BASE Intermediate_Dir "scws___Win32_Release_PHP53"
# PROP BASE Ignore_Export_Lib 0
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "../Release"
# PROP Intermediate_Dir "../Release"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MT /W3 /GX /Zi /O2 /D "WIN32" /D "NDEBUG" /D "_WINDOWS" /D "_MBCS" /D "_USRDLL" /D "SCWS_EXPORTS" /D "PHP_WIN32" /D "ZEND_WIN32" /D "ZTS" /D ZEND_DEBUG=0 /D "COMPILE_DL_SCWS" /YX /FD /c
# SUBTRACT BASE CPP /Fr
# ADD CPP /nologo /MD /W3 /GX /O2 /I "..\..\php-5.3.1" /I "..\..\php-5.3.1\main" /I "..\..\php-5.3.1\TSRM" /I "..\..\php-5.3.1\win32" /I "..\..\php-5.3.1\Zend" /I "..\libscws" /D "_WINDOWS" /D "_USRDLL" /D "SCWS_EXPORTS" /D ZTS=1 /D "COMPILE_DL_SCWS" /D "_USE_32BIT_TIME_T" /D "NDEBUG" /D "WIN32" /D "_MBCS" /D "PHP_WIN32" /D "ZEND_WIN32" /D ZEND_DEBUG=0 /Fr /YX /FD /c
# ADD BASE MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x804 /d "NDEBUG"
# ADD RSC /l 0x804 /d "NDEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /machine:I386
# ADD LINK32 libscws.lib zlib.lib php5ts.lib kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib /nologo /dll /pdb:none /machine:I386 /out:"../Release/php-5.3.x_vc6/php_scws.dll" /libpath:"../Release;../../php-5.3.1"
# SUBTRACT LINK32 /debug

!ENDIF 

# Begin Target

# Name "scws - Win32 Debug_PHP4"
# Name "scws - Win32 Release_PHP4"
# Name "scws - Win32 Debug_PHP5"
# Name "scws - Win32 Release_PHP5"
# Name "scws - Win32 Debug_PHP53"
# Name "scws - Win32 Release_PHP53"
# Begin Group "Source Files"

# PROP Default_Filter "cpp;c;cxx;rc;def;r;odl;idl;hpj;bat"
# Begin Source File

SOURCE=..\phpext\php_scws.c
# End Source File
# End Group
# Begin Group "Header Files"

# PROP Default_Filter "h;hpp;hxx;hm;inl"
# Begin Source File

SOURCE=..\phpext\php_scws.h
# End Source File
# End Group
# Begin Group "Resource Files"

# PROP Default_Filter "ico;cur;bmp;dlg;rc2;rct;bin;rgs;gif;jpg;jpeg;jpe"
# End Group
# End Target
# End Project
