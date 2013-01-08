/**
 * @file inaddr.c
 * @functions used sockaddr_storage 
 * modified from source of jabberd2.0s10
 * $Id$
 */

#include "xinaddr.h"

/**
 * set the address of a struct sockaddr_storage
 * (modeled after the stdlib function inet_pton)
 *
 * @param src the address that should be assigned to the struct sockaddr_storage
 * (either a dotted quad for IPv4 or a compressed IPv6 address)
 * @param dst the struct sockaddr_storage that should get the new address
 * @return 1 on success, 0 if address is not valid
 */
int xinet_pton(char *src, struct sockaddr_storage *dst)
{
#ifdef NO_INET_PTON
	struct sockaddr_in *sin;

	memset(dst, 0, sizeof(struct sockaddr_storage));
	sin = (struct sockaddr_in *)dst;
	
	if (inet_aton(src, &sin->sin_addr))
	{
		dst->ss_family = AF_INET;
		return 1;
	}
	return 0;

#else

	struct sockaddr_in *sin;
	struct sockaddr_in6 *sin6;

	memset(dst, 0, sizeof(struct sockaddr_storage));
	sin = (struct sockaddr_in *)dst;
	sin6 = (struct sockaddr_in6 *)dst;
	
	if (inet_pton(AF_INET, src, &sin->sin_addr) > 0)
	{
		dst->ss_family = AF_INET;
		return 1;
	}

	if (inet_pton(AF_INET6, src, &sin6->sin6_addr) > 0)
	{
		dst->ss_family = AF_INET6;
#ifdef SIN6_LEN
		sin6->sin6_len = sizeof(struct sockaddr_storage);
#endif
		return 1;
	}

	return 0;
#endif
}

/**
 * get the string representation of an address in struct sockaddr_storage
 * (modeled after the stdlib function inet_ntop)
 *
 * @param src the struct sockaddr_storage where the address should be read
 * @param dst where to write the result
 * @param size the size of the result buffer
 * @return NULL if failed, pointer to the result otherwise
 */
const char *xinet_ntop(struct sockaddr_storage *src, char *dst, size_t size)
{
#ifdef NO_INET_NTOP
	char *tmp;
	struct sockaddr_in *sin;

	sin = (struct sockaddr_in *)src;

	/* if we don't have inet_ntop we only accept AF_INET
	 * it's unlikely that we would have use for AF_INET6
	 */
	if (src->ss_family != AF_INET)	
		return NULL;	

	tmp = inet_ntoa(sin->sin_addr);

	if (!tmp || strlen(tmp) >= size)	
		return NULL;	

	strncpy(dst, tmp, size);
	return dst;

#else

	struct sockaddr_in *sin;
	struct sockaddr_in6 *sin6;

	sin = (struct sockaddr_in *) src;
	sin6 = (struct sockaddr_in6 *) src;

	switch (src->ss_family)
	{
		case AF_INET:
			return inet_ntop(AF_INET, &sin->sin_addr, dst, size);
		case AF_INET6:
			return inet_ntop(AF_INET6, &sin6->sin6_addr, dst, size);
		default:
			return NULL;
	}
#endif
}

/**
 * get the port number out of a struct sockaddr_storage
 *
 * @param sa the struct sockaddr_storage where we want to read the port
 * @return the port number (already converted to host byte order!)
 */
int xinet_getport(struct sockaddr_storage *sa)
{
	struct sockaddr_in *sin;
	struct sockaddr_in6 *sin6;
	
	switch(sa->ss_family)
	{
		case AF_INET:
			sin = (struct sockaddr_in *)sa;
			return ntohs(sin->sin_port);
		case AF_INET6:
			sin6 = (struct sockaddr_in6 *)sa;
			return ntohs(sin6->sin6_port);
		default:
			return 0;
	}
}

/**
 * set the port number in a struct sockaddr_storage
 *
 * @param sa the struct sockaddr_storage where the port should be set
 * @param port the port number that should be set (in host byte order)
 * @return 1 on success, 0 if address family is not supported
 */
int xinet_setport(struct sockaddr_storage *sa, in_port_t port)
{
	struct sockaddr_in *sin;
	struct sockaddr_in6 *sin6;

	sin = (struct sockaddr_in *)sa;
	sin6 = (struct sockaddr_in6 *)sa;

	switch(sa->ss_family)
	{
		case AF_INET:
			sin->sin_port = htons(port);
			return 1;
		case AF_INET6:
			sin6->sin6_port = htons(port);
			return 1;
		default:
			return 0;
	}
}

/**
 * calculate the size of an address structure
 * (on some unices the stdlibc functions for socket handling want to get the
 * size of the address structure that is contained in the
 * struct sockaddr_storage, not the size of struct sockaddr_storage itself)
 *
 * @param sa the struct sockaddr_storage for which we want to get the size of the contained address structure
 * @return the size of the contained address structure
 */
uint8_t xinet_addrlen(struct sockaddr_storage *sa)
{
	switch(sa->ss_family)
	{
		case AF_INET:
			return sizeof(struct sockaddr_in);
		case AF_INET6:
			return sizeof(struct sockaddr_in6);
		default:
			return sizeof(struct sockaddr_storage);
	}
}
