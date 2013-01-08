/*
 * @file mio/mio.c
 * @Managed Input/Output
 * modified from source of jabberd2.0s10
 * $Id$
 */

#include "mio.h"
#include "xinaddr.h"

#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <fcntl.h>
#include <errno.h>

#ifdef MIO_POLL
#	include "mio_poll.h"
#	if defined(MIO_SELECT)
#		undef MIO_SELECT
#	endif
#else
#	if !defined(MIO_SELECT)
#		define MIO_SELECT
#	endif
#endif

#ifdef MIO_SELECT
#	include "mio_select.h"
#endif

/* our internal wrapper around a fd */
typedef enum 
{ 
	type_CLOSED = 0x00, 
	type_NORMAL = 0x01, 
	type_LISTEN = 0x02, 
	type_CONNECT = 0x10, 
	type_CONNECT_READ = 0x11,
	type_CONNECT_WRITE = 0x12
}	mio_type_t;

struct mio_fd_st
{
	mio_type_t type;
	/* app event handler and data */
	mio_handler_t app;
	void *arg;
};

/* now define our master data type */
struct mio_st
{
	struct mio_fd_st *fds;
	int maxfd;
	int highfd;
	MIO_VARS
};

/* lazy factor */
#define FD(m,f)			m->fds[f]
#define ACT(m,f,a,d)	(*(FD(m,f).app))(m,a,f,d,FD(m,f).arg)

/* temp debug outputter */
#ifdef MIO_DEBUG
#include <stdarg.h>
#define ZONE	__LINE__
static void mio_debug(int line, const char *msgfmt, ...)
{
	va_list ap;
	va_start(ap, msgfmt);
	fprintf(stderr, "mio.c#%d: ", line);
	vfprintf(stderr, msgfmt, ap);
	fprintf(stderr, "\n");
}
#endif	/* MIO_DEBUG */

___MIO_FUNCS___

/** internal close function */
void mio_close(mio_t m, int fd)
{
#ifdef MIO_DEBUG
	mio_debug(ZONE, "actually closing fd #%d", fd);
#endif

	/* take out of poll sets */
	MIO_REMOVE_FD(m, fd);

	/* let the app know, it must process any waiting write data it has and free it's arg */
	ACT(m, fd, action_CLOSE, NULL);

	/* close the socket, and reset all memory */
	close(fd);
	memset(&FD(m,fd), 0, sizeof(struct mio_fd_st));
}

/** internally accept an incoming connection from a listen sock */
void _mio_accept(mio_t m, int fd)
{
	struct sockaddr_storage serv_addr;
	size_t addrlen = sizeof(serv_addr);
	int newfd, dupfd;
	char ip[INET6_ADDRSTRLEN];

#ifdef MIO_DEBUG
	mio_debug(ZONE, "accepting on fd #%d", fd);
#endif

	/* pull a socket off the accept queue and check */
	newfd = accept(fd, (struct sockaddr *)&serv_addr, (int *)&addrlen);
	if (newfd <= 0) return;

	xinet_ntop(&serv_addr, ip, sizeof(ip));

#ifdef MIO_DEBUG
	mio_debug(ZONE, "new socket accepted fd #%d, %s:%d", newfd, ip, xinet_getport(&serv_addr));
#endif

	/* set up the entry for this new socket */
	if (mio_fd(m, newfd, FD(m,fd).app, FD(m,fd).arg) < 0)
	{
		/* too high, try and get a lower fd */
		dupfd = dup(newfd);
		close(newfd);

		if (dupfd < 0 || mio_fd(m, dupfd, FD(m,fd).app, FD(m,fd).arg) < 0) {
#ifdef MIO_DEBUG
			mio_debug(ZONE, "failed to add fd");
#endif
			if (dupfd >= 0) close(dupfd);

			return;
		}

		newfd = dupfd;
	}

	/* tell the app about the new socket, if they reject it (!0) clean up */
	if (ACT(m, newfd, action_ACCEPT, ip) == 0)
	{
#ifdef MIO_DEBUG
		mio_debug(ZONE, "accept was rejected for %s:%d", ip, newfd);
#endif
		MIO_REMOVE_FD(m, newfd);

		/* close the socket, and reset all memory */
		close(newfd);
		memset(&FD(m, newfd), 0, sizeof(struct mio_fd_st));
	}

	return;
}

/** internally change a connecting socket to a normal one */
void _mio_connect(mio_t m, int fd)
{
	mio_type_t type = FD(m,fd).type;
#ifdef MIO_DEBUG
	mio_debug(ZONE, "connect processing for fd #%d", fd);
#endif

	/* reset type and clear the "write" event that flags connect() is done */
	FD(m,fd).type = type_NORMAL;
	MIO_UNSET_WRITE(m, fd);

	/* if the app had asked to do anything in the meantime, do those now */
	if (type & type_CONNECT_READ) mio_read(m,fd);
	if (type & type_CONNECT_WRITE) mio_write(m,fd);
}

/** add and set up this fd to this mio */
int mio_fd(mio_t m, int fd, mio_handler_t app, void *arg)
{
	int flags;

#ifdef MIO_DEBUG
	mio_debug(ZONE, "adding fd #%d", fd);
#endif

	if (fd >= m->maxfd)
	{
#ifdef MIO_DEBUG
		mio_debug(ZONE,"fd too high");
#endif
		return -1;
	}

	/* ok to process this one, welcome to the family */
	FD(m,fd).type = type_NORMAL;
	FD(m,fd).app = app;
	FD(m,fd).arg = arg;
	MIO_INIT_FD(m, fd);

	/* set the socket to non-blocking */
	flags =  fcntl(fd, F_GETFL, 0);
	flags |= O_NONBLOCK;
	fcntl(fd, F_SETFL, flags);

	/* track highest used */
	if (fd > m->highfd) m->highfd = fd;

	return fd;
}

/** reset app stuff for this fd */
void mio_app(mio_t m, int fd, mio_handler_t app, void *arg)
{
	FD(m,fd).app = app;
	FD(m,fd).arg = arg;
}

/** main select loop runner */
void mio_run(mio_t m, int timeout)
{
	int retval, fd;

#ifdef MIO_DEBUG
	mio_debug(ZONE, "mio running for %d", timeout);
#endif

	/* wait for a socket event */
	retval = MIO_CHECK(m, timeout);

	/* nothing to do */
	if (retval == 0) return;

	/* an error */
	if (retval < 0)
	{
#ifdef MIO_DEBUG
		mio_debug(ZONE, "MIO_CHECK returned an error (%d)", MIO_ERROR(m));
#endif
		return;
	}

#ifdef MIO_DEBUG
	mio_debug(ZONE, "mio working: %d", retval);
#endif

	/* loop through the sockets, check for stuff to do */
	for (fd = 0; fd <= m->highfd; fd++)
	{
		/* skip dead slots */
		if (FD(m,fd).type == type_CLOSED) continue;

		/* new conns on a listen socket */
		if (FD(m,fd).type == type_LISTEN && MIO_CAN_READ(m, fd))
		{
			_mio_accept(m, fd);
			continue;
		}

		/* check for connecting sockets */
		if (FD(m,fd).type & type_CONNECT && (MIO_CAN_READ(m, fd) || MIO_CAN_WRITE(m, fd)))
		{
			_mio_connect(m, fd);
			continue;
		}

		/* read from ready sockets */
		if (FD(m,fd).type == type_NORMAL && MIO_CAN_READ(m, fd))
		{
			/* if they don't want to read any more right now */
			if (ACT(m, fd, action_READ, NULL) == 0)
				MIO_UNSET_READ(m, fd);
		}

		/* write to ready sockets */
		if (FD(m,fd).type == type_NORMAL && MIO_CAN_WRITE(m, fd))
		{
			/* don't wait for writeability if nothing to write anymore */
			if (ACT(m, fd, action_WRITE, NULL) == 0)
				MIO_UNSET_WRITE(m, fd);
		}
	} 
}

/** eve */
mio_t mio_new(int maxfd)
{
	mio_t m;

	/* allocate and zero out main memory */
	if ((m = malloc(sizeof(struct mio_st))) == NULL) return NULL;
	if ((m->fds = malloc(sizeof(struct mio_fd_st) * maxfd)) == NULL)
	{
#ifdef MIO_DEBUG
		mio_debug(ZONE,"internal error creating new mio");
#endif
		free(m);
		return NULL;
	}
	memset(m->fds, 0, sizeof(struct mio_fd_st) * maxfd);

	/* set up our internal vars */
	m->maxfd = maxfd;
	m->highfd = 0;

	MIO_INIT_VARS(m);

	return m;
}

/** adam */
void mio_free(mio_t m)
{
	int fd;

	// hightman: close all opened fd first
	for (fd = 0; fd <= m->highfd; fd++)
	{
		/* skip dead slots */
		if (FD(m,fd).type == type_CLOSED) 
			continue;
		mio_close(m, fd);
	} 

	MIO_FREE_VARS(m);

	free(m->fds);
	free(m);
}

/** start processing read events */
void mio_read(mio_t m, int fd)
{
	if (m == NULL || fd < 0) return;

	/* if connecting, do this later */
	if (FD(m,fd).type & type_CONNECT)
	{
		FD(m,fd).type |= type_CONNECT_READ;
		return;
	}

	MIO_SET_READ(m, fd);
}

/** try writing to the socket via the app */
void mio_write(mio_t m, int fd)
{
	if (m == NULL || fd < 0) return;

	/* if connecting, do this later */
	if (FD(m,fd).type & type_CONNECT)
	{
		FD(m,fd).type |= type_CONNECT_WRITE;
		return;
	}

	if (ACT(m, fd, action_WRITE, NULL) == 0) 
		return;

	/* not all written, do more l8r */
	MIO_SET_WRITE(m, fd);
}

/** set up a listener in this mio w/ this default app/arg */
int mio_listen(mio_t m, int port, char *sourceip, mio_handler_t app, void *arg)
{
	int fd, flag = 1;
	struct sockaddr_storage sa;

	if (m == NULL) return -1;

#ifdef MIO_DEBUG
	mio_debug(ZONE, "mio to listen on %d [%s]", port, sourceip);
#endif

	memset(&sa, 0, sizeof(sa));

	/* if we specified an ip to bind to */
	if (sourceip != NULL && !xinet_pton(sourceip, &sa))
		return -1;

	if (sa.ss_family == 0)
		sa.ss_family = AF_INET;
	
	/* attempt to create a socket */
	if ((fd = socket(sa.ss_family,SOCK_STREAM,0)) < 0) return -1;
	if (setsockopt(fd, SOL_SOCKET, SO_REUSEADDR, (char*)&flag, sizeof(flag)) < 0) return -1;

	/* set up and bind address info */
	xinet_setport(&sa, port);
	if (bind(fd,(struct sockaddr*)&sa, xinet_addrlen(&sa)) < 0)
	{
		close(fd);
		return -1;
	}

	/* start listening with a max accept queue of 10 */
	if (listen(fd, 10) < 0)
	{
		close(fd);
		return -1;
	}

	/* now set us up the bomb */
	if (mio_fd(m, fd, app, arg) < 0)
	{
		close(fd);
		return -1;
	}
	FD(m,fd).type = type_LISTEN;

	/* by default we read for new sockets */
	mio_read(m,fd);

	return fd;
}

/** create an fd and connect to the given ip/port */
int mio_connect(mio_t m, int port, char *hostip, mio_handler_t app, void *arg)
{
	int fd, flag;
	struct sockaddr_storage sa;

	memset(&sa, 0, sizeof(sa));

	if (m == NULL || port <= 0 || hostip == NULL) return -1;

#ifdef MIO_DEBUG
	mio_debug(ZONE, "mio connecting to %s, port=%d",hostip,port);
#endif

	/* convert the hostip */
	if (xinet_pton(hostip, &sa)<=0)
		return -1;

	/* attempt to create a socket */
	if ((fd = socket(sa.ss_family,SOCK_STREAM,0)) < 0) return -1;

	/* set the socket to non-blocking before connecting */
	flag =  fcntl(fd, F_GETFL, 0);
	flag |= O_NONBLOCK;
	fcntl(fd, F_SETFL, flag);

	/* set up address info */
	xinet_setport(&sa, port);

	/* try to connect */
	flag = connect(fd,(struct sockaddr*)&sa, xinet_addrlen(&sa));

#ifdef MIO_DEBUG
	mio_debug(ZONE, "connect returned %d and %s", flag, strerror(errno));
#endif

	/* already connected?  great! */
	if (flag == 0 && mio_fd(m,fd,app,arg) == fd) return fd;

	/* gotta wait till later */
	if (flag == -1 && errno == EINPROGRESS && mio_fd(m,fd,app,arg) == fd)
	{
#ifdef MIO_DEBUG
		mio_debug(ZONE, "connect processing non-blocking mode");
#endif
		FD(m,fd).type = type_CONNECT;
		MIO_SET_WRITE(m,fd);
		return fd;
	}

	/* bummer dude */
	close(fd);
	return -1;
}
