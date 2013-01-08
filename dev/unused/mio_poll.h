/**
 * @file mio_poll.h
 * @MIO backend for poll()
 * @author Hightman Mar
 * $Id$
 */

#include <sys/poll.h>

#define ___MIO_FUNCS___ \
	static void _mio_pfds_init(mio_t m)								\
	{																\
		int fd;														\
		for(fd = 0; fd < m->maxfd; fd++)							\
			m->pfds[fd].fd = -1;									\
	}																\
																	\
	static int _mio_poll(mio_t m, int t)							\
	{																\
		return poll(m->pfds, m->highfd + 1, t*1000);				\
	}

#define MIO_VARS				struct pollfd *pfds;

#define MIO_INIT_VARS(m) \
	do {																\
		if((m->pfds = malloc(sizeof(struct pollfd) * maxfd)) == NULL)	\
		{																\
			free(m->fds);												\
			free(m);													\
			return NULL;												\
		}																\
		memset(m->pfds, 0, sizeof(struct pollfd) * maxfd);				\
		_mio_pfds_init(m);												\
	} while(0)

#define MIO_FREE_VARS(m)		free(m->pfds)

#define MIO_INIT_FD(m, pfd)		m->pfds[pfd].fd = pfd; m->pfds[pfd].events = 0

#define MIO_REMOVE_FD(m, pfd)	m->pfds[pfd].fd = -1

#define MIO_CHECK(m, t)			_mio_poll(m, t)

#define MIO_SET_READ(m, fd)		m->pfds[fd].events |= POLLIN
#define MIO_SET_WRITE(m, fd)	m->pfds[fd].events |= POLLOUT

#define MIO_UNSET_READ(m, fd)	m->pfds[fd].events &= ~POLLIN
#define MIO_UNSET_WRITE(m, fd)	m->pfds[fd].events &= ~POLLOUT

#define MIO_CAN_READ(m, fd)		m->pfds[fd].revents & (POLLIN|POLLERR|POLLHUP|POLLNVAL)
#define MIO_CAN_WRITE(m, fd)	m->pfds[fd].revents & POLLOUT

#define MIO_ERROR(m)			errno
