.PHONY: all clean

OBJS:=$(wildcard *.c)
OBJS:=$(OBJS:.c=.o)

all: a.out b.out

a.out: $(OBJS)
	gcc ./cli/scws_cmd.c *.o -I . -lm -o $@

b.out: $(OBJS)
	gcc ./cli/gen_dict.c *.o -I . -lm -o $@

%.o: %.c
	gcc $< -c

clean:
	rm -f $(OBJS)
	rm -f a.out
