/**
 * @file inaddr.h
 * @brief definitons and structures for sockaddr_storage
 * @author Hightman Mar
 * $Id$
 */

#ifndef _MIO_INADDR_H_
#define _MIO_INADDR_H_

#ifdef HAVE_CONFIG_H
#	include "config.h"
#endif

#include <string.h>
#include <sys/types.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <sys/socket.h>

/*
#define NO_IN6_ADDR
#define	NO_SOCKADDR_IN6
#define	NO_INET_PTON
#define	NO_INET_NTOP
*/

/* -------------------------------------------------------- */
/* define the structures that may be missing in old libc	*/
/* -------------------------------------------------------- */

#ifndef PF_INET6
#	define PF_INET6				10			/* protcol family for IPv6 */
#endif

#ifndef AF_INET6
#	define AF_INET6				PF_INET6	/* address family for IPv6 */
#endif

#ifndef INET6_ADDRSTRLEN
#	define INET6_ADDRSTRLEN		46			/* maximum length of the string representation of an IPv6 address */
#endif

/* check if an IPv6 is just a mapped IPv4 address */
#ifndef IN6_IS_ADDR_V4MAPPED
#define IN6_IS_ADDR_V4MAPPED(a) \
	((*(const uint32_t *)(const void *)(&(a)->s6_addr[0]) == 0) && \
	 (*(const uint32_t *)(const void *)(&(a)->s6_addr[4]) == 0) && \
	 (*(const uint32_t *)(const void *)(&(a)->s6_addr[8]) == ntohl(0x0000ffff)))
#endif

/* -------------------------------------------------------- */
/* structure that contains a plain IPv6 address				*/
/* (only defined if not contained in the libc)				*/
/* -------------------------------------------------------- */
#ifdef NO_IN6_ADDR
struct in6_addr {
	uint8_t		s6_addr[16];				/* IPv6 address */
};
#endif /* NO_IN6_ADDR */

/* -------------------------------------------------------- */
/* contains an IPv6 including some additional attributes	*/
/* (only defined if not contained in the libc)				*/
/* -------------------------------------------------------- */
#ifdef NO_SOCKADDR_IN6
struct sockaddr_in6 {
#ifdef SIN6_LEN
	uint8_t				sin6_len;			/* length of this struct */
#endif
	sa_family_t			sin6_family;		/* address family (AF_INET6) */
	in_port_t			sin6_port;			/* transport layer port # */
	uint32_t			sin6_flowinfo;		/* IPv6 traffic class and flow info */
	struct in6_addr		sin6_addr;			/* IPv6 address */
	uint32_t			sin6_scope_id;		/* set of interfaces for a scope */
};
#endif /* NO_SOCKADDR_IN6 */


#ifdef NO_SOCKADDR_STORAGE
/**
 * container for sockaddr_in and sockaddr_in6 structures, handled like
 * an object in jabberd2 code
 * (this definition is not fully compatible with RFC 2553,
 * but it is enough for us) 
 */
#define _SS_PADSIZE (128 - sizeof(sa_family_t))
struct sockaddr_storage {
	sa_family_t		ss_family;				/* address family */
	char			__ss_pad[_SS_PADSIZE];	/* padding to a size of 128 bytes */
};
#endif /* NO_SOCKADDR_STORAGE */

/* -------------------------------------------------------- */
/* functions used sockaddr_storage							*/
/* -------------------------------------------------------- */

int		xinet_pton(char *src, struct sockaddr_storage *dst);
const char	*xinet_ntop(struct sockaddr_storage *src, char *dst, size_t size);
int		xinet_getport(struct sockaddr_storage *sa);
int		xinet_setport(struct sockaddr_storage *sa, in_port_t port);
uint8_t	xinet_addrlen(struct sockaddr_storage *sa);

#endif /* _MIO_INADDR_H_ */
