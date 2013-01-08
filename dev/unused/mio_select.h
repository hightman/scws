/**
 * @file mio_select.h
 * @MIO backend for select()
 * @author Hightman Mar
 * $Id$
 */

#include <sys/select.h>
#include <sys/time.h>

#define ___MIO_FUNCS___ \
	static int _mio_select(mio_t m, int t)										\
	{																			\
		struct timeval tv;														\
																				\
		m->rfds_out = m->rfds_in;												\
		m->wfds_out = m->wfds_in;												\
																				\
		tv.tv_sec = t;															\
		tv.tv_usec = 0;															\
		return select(m->highfd + 1, &m->rfds_out, &m->wfds_out, NULL, &tv);	\
	}

#define MIO_VARS				fd_set rfds_in, wfds_in, rfds_out, wfds_out;

#define MIO_INIT_VARS(m)		FD_ZERO(&m->rfds_in); FD_ZERO(&m->wfds_in)

#define MIO_FREE_VARS(m)

#define MIO_INIT_FD(m, fd)

#define MIO_REMOVE_FD(m, fd)	do { FD_CLR(fd, &m->rfds_in); FD_CLR(fd, &m->wfds_in); } while(0)

#define MIO_CHECK(m, t)			_mio_select(m, t)

#define MIO_SET_READ(m, fd)		FD_SET(fd, &m->rfds_in)
#define MIO_SET_WRITE(m, fd)	FD_SET(fd, &m->wfds_in)

#define MIO_UNSET_READ(m, fd)	FD_CLR(fd, &m->rfds_in)
#define MIO_UNSET_WRITE(m, fd)	FD_CLR(fd, &m->wfds_in)

#define MIO_CAN_READ(m, fd)		FD_ISSET(fd, &m->rfds_out)
#define MIO_CAN_WRITE(m, fd)	FD_ISSET(fd, &m->wfds_out)

#define MIO_ERROR(m)			errno
