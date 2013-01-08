/**
 * @file mio.h
 * @brief mio - manage i/o
 * 
 * This used to be something large and all inclusive for 1.2/1.4,
 * but for 1.5 and beyond it is the most simple fd wrapper possible.
 * It is also customized per-app and may be limited/extended depending on needs.
 * 
 * Usage is pretty simple:
 *  - create a manager
 *  - add fds or tell it to listen
 *  - assign an action handler
 *  - tell mio to read or write with a fd
 *  - process accept, read, write, and close requests
 * 
 * Note: normal fd's don't get events unless the app calls mio_read/write() first!
 */

#ifndef _LIB_MIO_H_
#define _LIB_MIO_H_

#ifdef HAVE_CONFIG_H
#	include "config.h"
#endif

/* the master mio mama, defined internally */
typedef struct mio_st *mio_t;

/* these are the actions and a handler type assigned by the applicaiton using mio */
typedef enum { action_ACCEPT, action_READ, action_WRITE, action_CLOSE } mio_action_t;
typedef int (*mio_handler_t) (mio_t m, mio_action_t a, int fd, void* data, void *arg);

/* create/free the mio subsytem */
mio_t mio_new(int maxfd);
void mio_free(mio_t m);

/* for creating a new listen socket in this mio (returns new fd or <0) */
int mio_listen(mio_t m, int port, char *sourceip, mio_handler_t app, void *arg);

/* for creating a new socket connected to this ip:port (returns new fd or <0, use mio_read/write first) */
int mio_connect(mio_t m, int port, char *hostip, mio_handler_t app, void *arg);

/* tell mio to track this fd (returns new fd or <0) */
int mio_fd(mio_t m, int fd, mio_handler_t app, void *arg);

/* re-set the app handler */
void mio_app(mio_t m, int fd, mio_handler_t app, void *arg);

/* request that mio close this fd */
void mio_close(mio_t m, int fd);

/* mio should try the write action on this fd now */
void mio_write(mio_t m, int fd);

/* process read events for this fd */
void mio_read(mio_t m, int fd);

/* give some cpu time to mio to check it's sockets, 0 is non-blocking */
void mio_run(mio_t m, int timeout);

#endif  /* _LIB_MIO_H_ */
